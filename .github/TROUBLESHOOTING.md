# GitHub Actions Deployment Troubleshooting

## Common Errors and Solutions

### 1. SSH Connection Failed

**Error**: `Permission denied (publickey)` or `Connection refused`

**Solutions**:
- Verify `EC2_SSH_KEY` secret contains the complete private key (including BEGIN/END lines)
- Check that `EC2_HOST` is correct (use IP address if domain not configured)
- Verify `EC2_USER` matches your instance (usually `ubuntu` for Ubuntu, `ec2-user` for Amazon Linux)
- Ensure EC2 Security Group allows SSH (port 22) from GitHub Actions IPs
- Test SSH manually: `ssh -i your-key.pem ubuntu@YOUR_EC2_IP`

### 2. Permission Denied Errors

**Error**: `sudo: no tty present and no askpass program specified`

**Solutions**:
- Add user to sudoers without password:
  ```bash
  sudo visudo
  # Add this line:
  ubuntu ALL=(ALL) NOPASSWD: ALL
  ```

### 3. Composer Not Found

**Error**: `composer: command not found`

**Solutions**:
- Install Composer on EC2:
  ```bash
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
  sudo chmod +x /usr/local/bin/composer
  ```

### 4. PHP Extensions Missing

**Error**: `The requested PHP extension mbstring is missing`

**Solutions**:
- Install required PHP extensions:
  ```bash
  sudo apt install -y php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-mysql php8.2-zip php8.2-gd php8.2-curl
  ```

### 5. Storage Permission Errors

**Error**: `The stream or file could not be opened`

**Solutions**:
- Fix storage permissions:
  ```bash
  sudo chown -R www-data:www-data /var/www/html/storage
  sudo chmod -R 775 /var/www/html/storage
  sudo chmod -R 775 /var/www/html/bootstrap/cache
  ```

### 6. Environment File Missing

**Error**: `No application encryption key has been specified`

**Solutions**:
- Ensure `.env` file exists on EC2:
  ```bash
  cd /var/www/html
  cp .env.example .env
  php artisan key:generate
  ```

### 7. Database Connection Errors

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solutions**:
- Check `.env` database credentials
- Ensure MySQL/MariaDB is running: `sudo systemctl status mysql`
- Verify database exists and user has permissions
- Check Security Group allows database connections

### 8. Asset Files Not Found (404 errors)

**Error**: CSS/JS files return 404

**Solutions**:
- Ensure `public/js` and `public/css` directories exist
- Run `npm run build` on EC2 if needed
- Check web server configuration points to `public` directory
- Verify file permissions: `sudo chown -R www-data:www-data /var/www/html/public`

### 9. Route Cache Errors

**Error**: `Route cache not cleared`

**Solutions**:
- Clear route cache manually:
  ```bash
  php artisan route:clear
  php artisan config:clear
  php artisan cache:clear
  ```

### 10. GitHub Actions Workflow Fails

**Check workflow logs**:
1. Go to your GitHub repository
2. Click "Actions" tab
3. Click on the failed workflow run
4. Expand each step to see detailed error messages

**Common fixes**:
- Verify all secrets are set correctly
- Check EC2 instance is running
- Ensure internet connectivity from EC2
- Review error messages in workflow logs

## Testing Deployment Manually

Before using GitHub Actions, test deployment manually:

```bash
# On your local machine
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' \
  -e "ssh -i your-key.pem" ./ ubuntu@YOUR_EC2_IP:/var/www/html/

# SSH into EC2
ssh -i your-key.pem ubuntu@YOUR_EC2_IP

# Run deployment commands
cd /var/www/html
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
```

## Security Best Practices

1. **Never commit `.env` file** - It's already in `.gitignore`
2. **Use EC2 Security Groups** - Restrict SSH access to your IP only
3. **Rotate SSH keys** - Regularly update SSH keys
4. **Use IAM roles** - For AWS services, use IAM roles instead of access keys when possible
5. **Keep dependencies updated** - Regularly update Composer and NPM packages

