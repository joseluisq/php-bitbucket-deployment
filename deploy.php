<?php

/**
 * POST Hook Deployment from Bitbucket.org
 *
 * Simple PHP script to Bitbucket Deployment.
 *
 * @version   1.0.0
 * @author    Jose Luis Quintana <git.io/joseluisq>
 * @link      https://github.com/joseluisq/php-bitbucket-deployment
 */
 
 // Silent alerts
 error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

// Configuration
// =============

// Authentication
$username = 'username';
$password = 'password';

// Repository owner or team name
$owner = 'owner or team name';

// Repository name
$repository_name = 'repository_name';

// Destination path for deployment
$destination_path = './deployment_directory_path';

// ----------------------------------------------------------------------------------
// Secret access token is a "access_token" GET variable (usually a sha1 or md5 hash)
// Bitbucket POST Hook URL example:
// deploy.php?access_token=aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d
// ----------------------------------------------------------------------------------
$secret_access_token = 'SECRET_ACCESS_TOKEN';

// Time limit for script execution
$timelimit = 2000;

// ====================
// Automatic Deployment
// ====================
if (isset($_GET['access_token']) && !empty($_GET['access_token']) && $_GET['access_token'] === $secret_access_token) {
  if (isset($_POST['payload']) && !empty($_POST['payload'])) {
    $payload = json_decode(stripslashes($_POST['payload']), TRUE);
    
    if (!empty($payload) && isset($payload['commits']) && count($payload['commits']) > 0) {
      set_time_limit($timelimit);
      $node = $payload['commits'][0]['node'];
      $zipfile_path = get_zipfile_path_repo($owner, $repository_name, $node);
      $zipfile_name = get_zipfile_repo($zipfile_path, $username, $password);
      deployment($zipfile_name, $destination_path, $owner, $repository_name, $node);
    } else {
      die('Commit not found.');
    }
  } else {
    die('Payload not found.');
  }
} else {
  die('Access denied.');
}

// ===============
// Test deployment
// ===============
// deploy_temp.zip
// username-repository-b647897a24e9
// $node = 'b647897a24e9';
// deployment('deploy_temp.zip', $destination_path, $owner, $repository_name, $node);
// exit;

// =================
// Manual deployment
// =================
// $nodes = get_nodes($owner, $repository_name, $username, $password);
// $zipfile_path = get_zipfile_path_repo($owner, $repository_name, $nodes['node']);
// $zipfile_name = get_zipfile_repo($zipfile_path, $username, $password);
// deployment($zipfile_name, $destination_path, $repository_name, $nodes['node']);

function deployment($zipfile_name, $destination_path, $owner, $repo, $node) {
  
  // Checks if exists the last commit
  if (filesize($zipfile_name) < 50) {
    die('Commit not found.');
  }
  
  // Checks if destination path exists
  if (file_exists($destination_path)) {
    
    // Clears directory content
    remove_dir("$destination_path/", FALSE);
    
    // Extracts to destination path
    unzip_archive($zipfile_name, $destination_path);
    
    // Move repo files
    copy_dir("$destination_path/$owner-$repo-$node", $destination_path);
    
    // Remove repo default repo dir
    remove_dir("$destination_path/$owner-$repo-$node", TRUE);
    remove_dir("$destination_path/.git", TRUE);
    
    // Remove temp zip file
    @unlink($zipfile_name);
  } else {
    die('Path not found.');
  }
}

function unzip_archive($zipfile_name, $destination_path) {
  include_once 'unzip.php';
  $unzip = new Unzip();
  $unzip->extract($zipfile_name, "$destination_path/");
}

function get_nodes($owner, $repo, $username, $password) {
  $curl = curl_init("https://api.bitbucket.org/1.0/repositories/$owner/$repo/changesets?limit=1");
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
  curl_setopt($curl, CURLOPT_HEADER, FALSE);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
  curl_setopt($curl, CURLINFO_HEADER_OUT, true);
  $json = curl_exec($curl);
  curl_close($curl);
  $changesets = json_decode($json, TRUE);

  $node = $changesets['changesets'][0]['node'];
  $raw_node = $changesets['changesets'][0]['raw_node'];

  return array('node' => $node, 'raw_node' => $raw_node);
}

function get_zipfile_path_repo($owner, $repo, $node) {
  return "https://bitbucket.org/$owner/$repo/get/$node.zip";
}

function get_zipfile_repo($zipfile_path, $username, $password) {
  $filename = 'deploy_temp.zip';
  $fp = fopen($filename, 'w');
  $ch = curl_init($zipfile_path);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  
  // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_setopt($ch, CURLOPT_FILE, $fp);
  $data = curl_exec($ch);
  curl_close($ch);
  fclose($fp);
  return $filename;
}

function remove_dir($dir, $DeleteMe) {
  if (!$dh = @opendir($dir)) return;
  
  while (false !== ($obj = readdir($dh))) {
    if ($obj == '.' || $obj == '..') continue;
    if (!@unlink($dir . '/' . $obj)) remove_dir($dir . '/' . $obj, true);
  }
  
  closedir($dh);
  
  if ($DeleteMe) {
    @rmdir($dir);
  }
}

function copy_dir($src, $dst) {
  $dir = opendir($src);
  @mkdir($dst);
  
  while (false !== ($file = readdir($dir))) {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($src . '/' . $file)) {
        copy_dir($src . '/' . $file, $dst . '/' . $file);
      } else {
        copy($src . '/' . $file, $dst . '/' . $file);
      }
    }
  }
  
  closedir($dir);
}
