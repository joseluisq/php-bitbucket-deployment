# PHP Bitbucket Deployment

> Simple PHP script to automatic Bitbucket deployment.

### Settings

 1. For **private repositories** go to repository settings and add your
    server SSH key first.
 1. Then, in your **repository settings** add a new **POST** Service Hook.
 1. Enter the URL to your deployment script. E.g. `http://example.com/deploy.php?access_token=SECRET_ACCESS_TOKEN`.
 1. Finally **Save** hook.
 
### License
MIT License
