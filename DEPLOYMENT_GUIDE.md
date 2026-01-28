# Lavish Bites - Deployment & Production Setup Guide

## 🚀 Deployment Overview

This guide covers everything needed to deploy Lavish Bites to production with full POPIA compliance, SSL/HTTPS, and Stripe payment integration.

---

## 1️⃣ DOMAIN & HOSTING SETUP

### Register Domain

1. **Choose Domain Registrar:**
   - Namecheap
   - GoDaddy
   - Register.co.za (South African)
   - Afrihost (South African)

2. **Register:** `lavishbites.co.za`

3. **Choose Hosting Provider:**
   - Shared Hosting (budget-friendly)
   - VPS (recommended for better control)
   - Cloud (scalable, AWS/Azure/Google Cloud)

   **Requirements:**
   - PHP 7.4+ (for Stripe SDK)
   - cURL extension enabled
   - SSL/HTTPS support
   - Adequate storage (1GB+ recommended)
   - Email capability (for order confirmations)

### Recommended South African Hosts

- **Afrihost** - Excellent local support, affordable
- **Heroic** - Good uptime, professional
- **Hetzner** - Reliable, good pricing
- **Obsidian Systems** - Premium option with support

---

## 2️⃣ SSL/HTTPS CERTIFICATE INSTALLATION

### Why SSL is Critical

- ✅ Required by Stripe for payment processing
- ✅ Encrypts customer data in transit (256-bit TLS)
- ✅ Protects against man-in-the-middle attacks
- ✅ Improves Google search ranking
- ✅ Shows security badges to customers
- ✅ Required for POPIA compliance

### Installing Let's Encrypt (Free SSL)

**If using cPanel:**

1. Log in to cPanel
2. Find "AutoSSL" or "Let's Encrypt SSL" section
3. Click "Issue" for your domain
4. Select "Automatically use the newest Let's Encrypt certificate"
5. Click "Issue Certificate"
6. Wait 5-10 minutes for activation

**If using Plesk:**

1. Log in to Plesk
2. Go to Websites & Domains
3. Select your domain
4. Go to SSL/TLS Certificates
5. Click "Get Free Basic Certificate"
6. Select Let's Encrypt
7. Click "Get It Free"

**If using Linux/Command Line:**

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Generate certificate
sudo certbot certonly --standalone -d lavishbites.co.za

# Auto-renew (runs daily)
sudo certbot renew --quiet
```

### Premium SSL Options

If your hosting doesn't support Let's Encrypt:

- **Comodo SSL** (~$50/year)
- **Sectigo** (~$40/year)
- **RapidSSL** (~$30/year)

---

## 3️⃣ STRIPE PAYMENT SETUP

### Create Stripe Account

1. **Go to:** https://dashboard.stripe.com/register
2. **Sign up** with business email
3. **Verify email** and activate account
4. **Complete business profile:**
   - Business name: Lavish Bites
   - Country: South Africa
   - Currency: ZAR (South African Rand)
   - Website: lavishbites.co.za

### Get Live API Keys

**Stripe Dashboard > API Keys:**

1. Click "Developers" in sidebar
2. Select "API Keys" tab
3. Switch toggle to "Live" (not Test)
4. Copy **Publishable Key** (starts with `pk_live_`)
5. Copy **Secret Key** (starts with `sk_live_`)
   - ⚠️ **NEVER SHARE SECRET KEY** - Keep it private!

### Configure Keys in Code

**Update index.html (Line ~915):**

```javascript
// REPLACE THIS:
const stripe = Stripe('pk_test_your_publishable_key_here');

// WITH THIS (your live key):
const stripe = Stripe('pk_live_YOUR_ACTUAL_PUBLISHABLE_KEY');
```

**Update create-checkout-session.php (Line 6):**

```php
// REPLACE THIS:
\Stripe\Stripe::setApiKey('sk_test_your_secret_key_here');

// WITH THIS (your live key):
\Stripe\Stripe::setApiKey('sk_live_YOUR_ACTUAL_SECRET_KEY');
```

### Best Practice: Environment Variables

Instead of hardcoding keys, use environment variables:

**Create .env file in root:**
```
STRIPE_LIVE_PUBLIC_KEY=pk_live_xxxxx
STRIPE_LIVE_SECRET_KEY=sk_live_xxxxx
```

**Update create-checkout-session.php:**
```php
<?php
require 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$stripe_key = $_ENV['STRIPE_LIVE_SECRET_KEY'];
\Stripe\Stripe::setApiKey($stripe_key);
```

**Update index.html:**
```javascript
// Use data attribute or fetch from PHP endpoint
fetch('get-stripe-key.php')
    .then(response => response.json())
    .then(data => {
        const stripe = Stripe(data.publishableKey);
    });
```

---

## 4️⃣ CONFIGURE STRIPE WEBHOOK URLS

### Set Webhook Endpoints

1. **Stripe Dashboard > Developers > Webhooks**
2. **Click "Add endpoint"**
3. **Endpoint URL:** `https://lavishbites.co.za/webhook.php`
4. **Events to receive:** Select all relevant events:
   - `charge.succeeded`
   - `charge.failed`
   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
5. **Click "Add endpoint"**

### Create webhook.php

Create file: `webhook.php`

```php
<?php
require 'vendor/autoload.php';

$stripe_secret = getenv('STRIPE_LIVE_SECRET_KEY');
\Stripe\Stripe::setApiKey($stripe_secret);

$input = file_get_contents('php://input');
$event = json_decode($input);

try {
    if (!$event || !$event->type) {
        http_response_code(400);
        exit();
    }

    switch($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            
            // Send confirmation email
            $to = $session->customer_email;
            $subject = 'Order Confirmation - Lavish Bites';
            $message = "Thank you for your order!\n\n";
            $message .= "Order ID: " . $session->id . "\n";
            $message .= "Amount: R" . ($session->amount_total / 100) . "\n";
            $message .= "Status: Confirmed\n\n";
            $message .= "We'll prepare your order and contact you with delivery details.";
            
            mail($to, $subject, $message);
            
            // Log order
            error_log("Order confirmed: " . $session->id);
            break;
            
        case 'charge.failed':
            $charge = $event->data->object;
            error_log("Payment failed: " . $charge->id);
            break;
    }
    
    http_response_code(200);
    
} catch(Exception $e) {
    error_log($e->getMessage());
    http_response_code(400);
}
?>
```

---

## 5️⃣ UPDATE REDIRECT URLS

### Stripe Checkout Settings

**In create-checkout-session.php (Lines 44-45):**

```php
// REPLACE THIS:
'success_url' => 'http://localhost:8000/success.php',
'cancel_url' => 'http://localhost:8000/index.html',

// WITH THIS:
'success_url' => 'https://lavishbites.co.za/success.php',
'cancel_url' => 'https://lavishbites.co.za/index.html',
```

### Update Email Configuration

**In webhook.php and create-checkout-session.php:**

```php
// Email settings
$from_email = 'orders@lavishbites.co.za';
$from_name = 'Lavish Bites';

$headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

mail($to_email, $subject, $message, $headers);
```

---

## 6️⃣ FILE UPLOAD & DEPLOYMENT

### Via FTP/SFTP

1. **Download FileZilla or WinSCP**
2. **Connect with:**
   - Host: your.hosting.com
   - Username: your_username
   - Password: your_password
   - Port: 21 (FTP) or 22 (SFTP)
3. **Upload to:** `public_html/` directory
4. **File Structure:**
   ```
   public_html/
   ├── index.html
   ├── menu.html
   ├── testimonial.html
   ├── privacy-policy.html
   ├── terms-conditions.html
   ├── payment-security.html
   ├── success.php
   ├── create-checkout-session.php
   ├── webhook.php
   ├── css/
   ├── js/
   ├── img/
   ├── lib/
   └── .env (keep private - outside public_html)
   ```

### Via Git/Command Line

```bash
# Clone repository
git clone https://github.com/yourusername/lavish-bites.git

# Navigate to directory
cd lavish-bites

# Create .env file (don't commit this)
echo "STRIPE_LIVE_SECRET_KEY=sk_live_xxxxx" > .env

# Push to your hosting via SSH
ssh your_user@your_host.com
cd public_html/
git pull origin main
```

---

## 7️⃣ DATABASE SETUP (Optional)

### For Advanced Order Tracking

If you want to store orders in a database:

**Create database in cPanel:**
1. cPanel > MySQL Databases
2. Create database: `lavish_bites_orders`
3. Create user: `lavish_bites_user`
4. Set password (strong!)
5. Add user to database with all privileges

**Create orders table:**

```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(255) UNIQUE,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    order_items LONGTEXT,
    order_total DECIMAL(10, 2),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_address TEXT,
    special_requests TEXT,
    status VARCHAR(50),
    payment_status VARCHAR(50),
    stripe_session_id VARCHAR(255)
);
```

**Store orders in webhook:**

```php
<?php
// Connect to database
$db = new mysqli('localhost', 'lavish_bites_user', 'password', 'lavish_bites_orders');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

// Insert order
$stmt = $db->prepare("INSERT INTO orders (order_id, customer_email, order_total, status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssds", $session->id, $session->customer_email, $total, $status);
$status = 'pending';
$total = $session->amount_total / 100;
$stmt->execute();
?>
```

---

## 8️⃣ EMAIL CONFIGURATION

### Enable PHP Mail

**In cPanel:**
1. Go to WHM > Service Configuration > Mail
2. Ensure Exim is running
3. Verify SPF/DKIM records

### Use SMTP for Reliability

**Update PHP to use SMTP:**

```php
<?php
$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';  // Or your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'orders@lavishbites.co.za';
    $mail->Password = 'your_app_password';  // Not your Gmail password!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom('orders@lavishbites.co.za', 'Lavish Bites');
    $mail->addAddress($customer_email);
    $mail->Subject = 'Your Order Confirmation';
    $mail->Body = $message;
    
    $mail->send();
} catch (Exception $e) {
    error_log('Mailer Error: ' . $mail->ErrorInfo);
}
?>
```

---

## 9️⃣ TESTING BEFORE GOING LIVE

### Test Checklist

**Security:**
- [ ] HTTPS working (green lock icon visible)
- [ ] No mixed content warnings (all resources load via HTTPS)
- [ ] SSL Labs score A or higher (https://www.ssllabs.com/ssltest/)

**Stripe Integration:**
- [ ] Test payment with test card: 4242 4242 4242 4242
- [ ] Verify success page loads after payment
- [ ] Check Stripe dashboard for transactions
- [ ] Test refund process
- [ ] Verify emails are sent after payment

**Mobile Testing:**
- [ ] Test on iPhone, Android phones
- [ ] Test all forms work on mobile
- [ ] Test cart system on mobile
- [ ] Test WhatsApp button opens app
- [ ] Test payment on mobile

**Forms:**
- [ ] All required fields validated
- [ ] Consent checkbox required before payment
- [ ] No data lost on page refresh
- [ ] Form error messages clear

**Compliance:**
- [ ] Privacy Policy accessible and complete
- [ ] Terms & Conditions accessible and complete
- [ ] Payment Security page accessible
- [ ] Footer links all work
- [ ] POPIA notice visible in footer

---

## 🔟 LAUNCH CHECKLIST

- [ ] Domain registered and pointing to hosting
- [ ] SSL certificate installed (HTTPS working)
- [ ] Stripe live keys configured
- [ ] Webhook URLs set up
- [ ] Email configuration working
- [ ] All security headers set
- [ ] CORS properly configured if needed
- [ ] Backup system in place
- [ ] Monitoring/logging enabled
- [ ] Google Search Console verified
- [ ] Google Analytics installed (optional)
- [ ] Robots.txt optimized for SEO

---

## 1️⃣1️⃣ POST-LAUNCH MONITORING

### Regular Maintenance

**Daily:**
- Monitor Stripe dashboard for transactions
- Check for payment failures
- Review email logs for errors

**Weekly:**
- Review customer feedback/complaints
- Check website uptime
- Verify backups completed

**Monthly:**
- Security patch updates
- Stripe fee review
- Performance optimization
- Customer data audit (POPIA compliance)

### Setup Monitoring

**Uptime Monitoring:**
- Uptimerobot.com (free tier available)
- Alert if site goes down
- Email notifications

**Error Logging:**
- Set up error logs to email
- Monitor PHP errors
- Track payment failures

---

## 1️⃣2️⃣ TROUBLESHOOTING

### Stripe Payment Not Working

**Issue:** "Stripe is not defined"
```
Solution: Check that Stripe.js loads before your code:
<script src="https://js.stripe.com/v3/"></script>
```

**Issue:** "Payment processing failed"
```
Solution: 
1. Verify API keys are correct
2. Check SSL certificate (must be HTTPS)
3. Ensure firewall isn't blocking Stripe
4. Check server has cURL enabled
```

### SSL Certificate Issues

**Issue:** Mixed content warning
```
Solution: Change all links to HTTPS:
<script src="https://cdn.example.com/file.js"></script>
<link href="https://cdn.example.com/file.css" rel="stylesheet">
```

**Issue:** Certificate not trusted
```
Solution: 
1. Ensure certificate is properly installed
2. Restart web server
3. Check certificate chain (should have 3 certificates)
4. Force HTTPS redirect
```

### Email Not Sending

**Issue:** Orders not receiving confirmation emails
```
Solution:
1. Check mail server logs
2. Verify SPF/DKIM records
3. Use SMTP instead of mail() function
4. Test with PHPMailer
5. Check spam folder
```

---

## 1️⃣3️⃣ BACKUP & DISASTER RECOVERY

### Automated Backups

**Set up weekly backups:**

```bash
#!/bin/bash
# backup.sh - Run weekly via cron

BACKUP_DIR="/home/backups"
DATE=$(date +%Y%m%d)
DOMAIN="lavishbites.co.za"

# Backup website files
tar -czf $BACKUP_DIR/${DOMAIN}_files_${DATE}.tar.gz /home/user/public_html/

# Backup database
mysqldump -u user -p'password' lavish_bites_orders > $BACKUP_DIR/${DOMAIN}_db_${DATE}.sql

# Delete old backups (keep 30 days)
find $BACKUP_DIR -name "${DOMAIN}_*" -mtime +30 -delete
```

### Restore Process

```bash
# Restore files
tar -xzf lavishbites.co.za_files_20240101.tar.gz

# Restore database
mysql -u user -p'password' lavish_bites_orders < lavishbites.co.za_db_20240101.sql
```

---

## 📞 Support & Resources

**Stripe Support:** https://support.stripe.com
**SSL Support:** https://letsencrypt.org/docs/
**PHP Documentation:** https://www.php.net/docs.php
**Hosting Support:** Contact your hosting provider
**Lavish Bites:** info@lavishbites.co.za

---

**Last Updated:** January 2024
**Status:** ✅ Ready for Production Deployment
