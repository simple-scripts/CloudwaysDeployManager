# Cloudways Deploy Manager

A Laravel 10+ package that is intended to only run on a developer local machine. It will create a local DB tables to 
store your Cloudways Server and App credentials.

## Quick overview commands:

- `cw:deploy:laravel` Run all commands to deploy a Laravel app on Cloudways via SSH
- [cw:manager:composer-auth](#update-the-composerauthjson-creds) Upload/update the remote ~/.composer/auth.json file
- [cw:manager:export](#server-credentials) Export saved CloudServers to JSON file, excluding local
- [cw:manager:import](#server-credentials) Import CloudServers from database/JSON/servers.json file
- [cw:manager:ssh](#ssh-keys) Upload SSH Key as defined in the .env


## Installation

You can install the package via composer:

```bash
composer require simple-scripts/cloudways-deploy-manager
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="clouddeploymanager-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="clouddeploymanager-config"
```

## ENV

Add to your .env:

For package:
```dotenv
CLOUDWAYS_EMAIL=
CLOUDWAYS_API_KEY=
```

Cloudways Deploy manager settings:
```dotenv

# Set this to something like: name_cloud_deploy_manager
CW_DM_APP_CREDENTIALS_USERNAME=name_cloud_deploy_manager
# SSH Keys to be able to log into Cloudways servers
# This is required to run related commands
# If you have not set up a ssh key for Cloudways see: https://support.cloudways.com/en/articles/5120579-why-should-you-set-up-ssh-keys#h_4163feed5d
CW_DM_SSH_KEY_PUBLIC_PATH="C:\\Users\\username\\.ssh\\key.pub"
CW_DM_SSH_PUBLIC_KEY="ssh-rsa ***"
CW_DM_CW_DM_SSH_PRIVATE_KEY_PATH="C:\\Users\\username\\.ssh\\cloudways_putty.ppk"
CW_DM_SSH_KEY_PPK_PRIVATE_PATH="C:\\Users\\username\\.ssh\\cloudways_putty.ppk"
# Name of uploaded SSK key and will be visible in Cloudways 
# ex: MyName Dev Laptop
CW_DM_SSH_KEY_UPLOADED_NAME="MyName Dev Laptop"

# Windows only:
CW_DM_SSH_USE_PLINK=true
```

## Event

- \SimpleScripts\CloudDeployManager\Events\CloudwaysDeployLaravelPrependRemoteSsh - Listen to this event and add more custom 
commands to run for your Laravel Deploy

## Project set up

A package is intended to only run on a developer local machine.

Install via composer on a Laravel 10+ project.


### Server credentials

- New/Manually create with a DB tool like HeidiSQL or Sequel
  - Server: create a new record in the cloudways_servers table 
    - Fill in the needed fields by finding values in the Cloudways Server UI page
    - All data can be found on the cloudways server page
      - Do not fill in the cloudways_servers.ssh_key_id
  - Apps: create a new record in the cloudways_apps table, you first need to add in a server record as noted above
    - Leave the cloudways_apps.cred_id NULL
    - Find other fields in the Cloudways App UI page

### SSH Keys

SSH Keys are needed for every server application to run composer install and other various commands. Follow the steps below
to create them for every server application.

- Setup your local ssh key as n
- You will need to create one local .ssh key
    - on windows install Putty and use option 3: https://www.howtogeek.com/762863/how-to-generate-ssh-keys-in-windows-10-and-windows-11/
    - Linux/Mac: https://www.makeuseof.com/ssh-keygen-mac/
        - run `ssh-keygen -t rsa` and save to your preferred location
- Now set the correct path values in the .env for your generated keys
- Run the following command to push your generated keys to the cloudways servers in your DB:
```shell
php artisan cloudways:ssh
```
If it is your first time to connect to a server via an SSH Key you will need to confirm the keys before you can run update
commands. If you ran the `php artisan cw:manager:ssh` command it should have created the _ssh/confirm.sh file. You can run this
from your terminal like so: `sh _ssh/confirm.sh` and then you will need to confirm each one. Windows/Plink also will
ask you to hit enter to continue. Now you will have a valid ssh session, but you will want to kill it, type exit and then
press enter. It will then go to the next one in the list until all have been completed.

Now you are ready to run some commands!

### Update the ~/.composer/auth.json creds

If you use private packages or need to have an auth.json file for your Apps, you can automate this to many servers.
Github API keys expire after a time and need to be updated. Create a valid file: database/JSON/auth.json with a valid Github API key. It will

```json
{
    "github-oauth": {
        "github.com": "ghp_..."
    },
    "http-basic": {
        "your-private-satis.com": {
            "username": "....",
            "password": "...."
        }
    }
}
```
Then run:
`php artisan cw:manager:composer-auth` see the help for more info

## Development

This project is using the [`spatie/ssh`](https://github.com/spatie/ssh) and
[`rockbuzz/lara-cwapi`](https://github.com/rockbuzz/lara-cwapi) packages. The Ssh package runs composer install commands
and the lara-cwapi is the basic package to connect to the Cloudways API.
