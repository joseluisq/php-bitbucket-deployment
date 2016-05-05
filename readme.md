# PHP Bitbucket Deployment

> Simple PHP script to automatic Bitbucket deployment.

### Settings

 1. First, copy `deploy.php` and `unzip.php` files on your server directory.
 1. If your repository is private you shoud go to your account settings and add some SSH key before.
 1. On your repository go to `Settings > Integrations > Hooks` and add a new **POST Service Hook**.
 1. Enter the URL of your `deploy.php` script. E.g. `http://your-server.com/deploy.php?access_token=YOUR_ACCESS_TOKEN`.
 1. Finally **Save** hook.

## License
MIT license

© 2016 [José Luis Quintana](http://git.io/joseluisq)
