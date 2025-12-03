# SSH Key Troubleshooting Guide

## Common Issues and Solutions

### Issue: Permission denied (publickey)

This means the SSH key authentication is failing. Follow these steps:

1. **Verify SSH Key Format in GitHub Secrets**

    - Go to GitHub → Settings → Secrets and variables → Actions
    - Check that `EC2_SSH_KEY` contains the ENTIRE private key including:
        - `-----BEGIN RSA PRIVATE KEY-----` (or `-----BEGIN OPENSSH PRIVATE KEY-----`)
        - All the key content
        - `-----END RSA PRIVATE KEY-----` (or `-----END OPENSSH PRIVATE KEY-----`)
    - Make sure there are NO extra spaces or line breaks at the beginning/end

2. **Verify Public Key is on EC2**

    - On your local machine, check if you have the public key:
        ```bash
        cat ~/.ssh/ec2_deploy_key.pub
        ```
    - SSH into your EC2 instance:
        ```bash
        ssh -i YOUR_EC2_KEY.pem ubuntu@YOUR_EC2_IP
        ```
    - Check if the public key is in authorized_keys:
        ```bash
        cat ~/.ssh/authorized_keys
        ```
    - If the public key is NOT there, add it:

        ```bash
        # On your local machine, copy the public key
        cat ~/.ssh/ec2_deploy_key.pub
        # Copy the output

        # On EC2, add it:
        mkdir -p ~/.ssh
        echo "PASTE_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys
        chmod 600 ~/.ssh/authorized_keys
        chmod 700 ~/.ssh
        ```

3. **Test SSH Connection Manually**

    - On your local machine, test the connection:
        ```bash
        ssh -i ~/.ssh/ec2_deploy_key -v ubuntu@YOUR_EC2_IP
        ```
    - If this works, the key is correct. If not, regenerate the key pair.

4. **Regenerate SSH Key Pair (if needed)**

    ```bash
    # Delete old keys (if they exist)
    rm ~/.ssh/ec2_deploy_key ~/.ssh/ec2_deploy_key.pub

    # Generate new key pair
    ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f ~/.ssh/ec2_deploy_key -N ""

    # Copy public key to EC2
    ssh-copy-id -i ~/.ssh/ec2_deploy_key.pub ubuntu@YOUR_EC2_IP

    # Or manually:
    cat ~/.ssh/ec2_deploy_key.pub
    # Then on EC2, add it to ~/.ssh/authorized_keys

    # Update GitHub Secret with new private key
    cat ~/.ssh/ec2_deploy_key
    # Copy the entire output (including BEGIN and END lines) to GitHub Secret EC2_SSH_KEY
    ```

5. **Verify GitHub Secrets are Set**
    - `EC2_HOST`: Your EC2 public IP (e.g., `3.123.45.67`)
    - `EC2_USER`: `ubuntu` (for Ubuntu instances)
    - `EC2_SSH_KEY`: Your complete private key
    - `EC2_DEPLOY_PATH`: `/var/www/html` (optional)

---

## Issue: Cannot Access Application in Browser

### Step 1: Check Security Group

1. Go to AWS Console → EC2 → Security Groups
2. Select your EC2 instance's security group
3. **Inbound Rules** should allow:
    - **HTTP (port 80)** from `0.0.0.0/0` (or your IP)
    - **HTTPS (port 443)** from `0.0.0.0/0` (optional)
    - **SSH (port 22)** from your IP (for security)

### Step 2: Check if Containers are Running

SSH into EC2 and run:

```bash
ssh -i ~/.ssh/ec2_deploy_key ubuntu@54.188.81.175
cd /var/www/html
docker-compose -f docker-compose.prod.yml ps
```

You should see 3 containers running:

-   `laravel-app-prod` (status: Up)
-   `laravel-web-prod` (status: Up)
-   `laravel-db-prod` (status: Up)

### Step 3: Check Container Logs

```bash
# Check all containers
docker-compose -f docker-compose.prod.yml logs

# Check specific container
docker-compose -f docker-compose.prod.yml logs web
docker-compose -f docker-compose.prod.yml logs app
docker-compose -f docker-compose.prod.yml logs db
```

### Step 4: Check if Port 80 is Listening

```bash
sudo netstat -tlnp | grep :80
# or
sudo ss -tlnp | grep :80
```

You should see something like:

```
tcp  0  0  0.0.0.0:80  0.0.0.0:*  LISTEN  <PID>/docker-proxy
```

### Step 5: Test from EC2 Itself

```bash
curl http://localhost
# or
curl http://127.0.0.1
```

If this works, the app is running but security group might be blocking external access.

### Step 6: Check .env File

```bash
cat /var/www/html/.env | grep APP_URL
```

Make sure `APP_URL` is set correctly:

```env
APP_URL=http://54.188.81.175
```

### Step 7: Restart Containers

```bash
cd /var/www/html
docker-compose -f docker-compose.prod.yml restart
# or
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d
```

### Step 8: Check Nginx Configuration

```bash
docker-compose -f docker-compose.prod.yml exec web nginx -t
```

### Common Fixes:

1. **Security Group Not Configured**

    - Add HTTP (port 80) rule in AWS Console

2. **Containers Not Running**

    - Check logs: `docker-compose -f docker-compose.prod.yml logs`
    - Restart: `docker-compose -f docker-compose.prod.yml restart`

3. **Wrong APP_URL**

    - Update `.env`: `APP_URL=http://54.188.81.175`

4. **Port Conflict**

    - Check if another service is using port 80: `sudo lsof -i :80`
    - Stop conflicting service or change Docker port mapping

5. **Database Connection Issues**
    - Check `.env` has correct DB credentials
    - Check DB container is running: `docker-compose -f docker-compose.prod.yml ps db`
