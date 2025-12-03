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
