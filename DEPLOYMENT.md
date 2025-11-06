# Deployment Guide for EC2 with GitHub Actions

## Prerequisites

1. EC2 instance running Ubuntu/Linux
2. PHP 8.2+ installed
3. Composer installed
4. Node.js and NPM installed
5. Web server (Apache/Nginx) configured
6. SSH access to EC2 instance

## GitHub Secrets Setup

Go to your GitHub repository → **Settings → Secrets and variables → Actions**, and add the following secrets:

1. **EC2_HOST**: Your EC2 instance public IP or domain (e.g., `ec2-xx-xx-xx-xx.compute-1.amazonaws.com` or `3.123.45.67`)
2. **EC2_USER**: SSH username (usually `ubuntu` for Ubuntu instances, `ec2-user` for Amazon Linux)
3. **EC2_SSH_KEY**: Your private SSH key content (the entire key including `-----BEGIN RSA PRIVATE KEY-----` and `-----END RSA PRIVATE KEY-----`)
4. **EC2_DEPLOY_PATH**: (Optional) Deployment path on server (default: `/var/www/html`)

### How to Get Your SSH Key

**Option 1: Use existing EC2 key pair**
1. Download your `.pem` file from AWS EC2 Console
2. Copy the entire content of the `.pem` file to GitHub Secret `EC2_SSH_KEY`

**Option 2: Generate new key pair**
```bash
ssh-keygen -t rsa -b 4096 -C "deploy@github-actions" -f ~/.ssh/ec2_deploy_key
```

Then:
1. Copy public key to EC2: `ssh-copy-id -i ~/.ssh/ec2_deploy_key.pub ubuntu@YOUR_EC2_IP`
2. Copy private key content (`cat ~/.ssh/ec2_deploy_key`) to GitHub Secret `EC2_SSH_KEY`

## How to Get Your SSH Key

If you don't have an SSH key pair:

1. Generate a new key pair:
   ```bash
   ssh-keygen -t rsa -b 4096 -C "your_email@example.com" -f ~/.ssh/ec2_deploy_key
   ```

2. Copy the public key to your EC2 instance:
   ```bash
   ssh-copy-id -i ~/.ssh/ec2_deploy_key.pub ubuntu@YOUR_EC2_IP
   ```

3. Copy the private key content (`~/.ssh/ec2_deploy_key`) to GitHub Secrets as `EC2_SSH_KEY`

## EC2 Instance Setup

### 1. Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Git
sudo apt install -y git

# Install Apache/Nginx (choose one)
# For Apache:
sudo apt install -y apache2 libapache2-mod-php8.2
# OR for Nginx:
# sudo apt install -y nginx php8.2-fpm
```

### 2. Clone Repository on EC2

```bash
cd /var/www/html
sudo git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git .
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache
```

### 3. Configure Environment

```bash
cd /var/www/html
sudo cp .env.example .env
sudo nano .env
# Edit .env with your database credentials and other settings
sudo php artisan key:generate
```

### 4. Set Up Web Server

**For Apache:**
```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**For Nginx:**
```bash
sudo nano /etc/nginx/sites-available/default
```

Add:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

## Deployment Workflows

Two workflow files are provided:

1. **`.github/workflows/deploy.yml`**: Full deployment with file transfer (recommended for first-time setup)
2. **`.github/workflows/deploy-simple.yml`**: Simple Git-based deployment (requires Git setup on EC2)

Choose the one that fits your setup. The simple one requires Git to be configured on EC2 with your repository cloned.

## Troubleshooting

### Common Issues:

1. **Permission Denied**: Make sure SSH key has correct permissions (600)
2. **Composer not found**: Ensure Composer is in PATH or use full path
3. **Storage permission errors**: Run `sudo chmod -R 775 storage bootstrap/cache`
4. **Environment file missing**: Ensure `.env` exists on server
5. **Database connection errors**: Check `.env` database credentials

### Check Logs:

```bash
# GitHub Actions logs
# Go to Actions tab in your GitHub repository

# EC2 application logs
tail -f /var/www/html/storage/logs/laravel.log

# Web server logs
# Apache:
tail -f /var/log/apache2/error.log
# Nginx:
tail -f /var/log/nginx/error.log
```

