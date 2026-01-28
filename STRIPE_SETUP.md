# Stripe Payment Setup Guide for Lavish Bites

## Quick Setup Instructions

### Step 1: Get Your Stripe Keys
1. Go to https://stripe.com and sign up for a free account
2. Verify your email and complete your account setup
3. Go to Dashboard → Developers → API Keys
4. Copy your **Publishable Key** and **Secret Key**

### Step 2: Update Your Files

#### In `index.html` (around line 720):
Replace this line:
```javascript
const stripe = Stripe('pk_test_your_publishable_key_here');
```
With your actual publishable key:
```javascript
const stripe = Stripe('pk_live_YOUR_ACTUAL_KEY_HERE');
```

#### In `create-checkout-session.php` (line 6):
Replace:
```php
\Stripe\Stripe::setApiKey('sk_test_your_secret_key_here');
```
With your actual secret key:
```php
\Stripe\Stripe::setApiKey('sk_live_YOUR_ACTUAL_KEY_HERE');
```

#### In `create-checkout-session.php` (line 49-50):
Update the success and cancel URLs:
```php
'success_url' => 'https://yourdomain.com/success.php?session_id={CHECKOUT_SESSION_ID}',
'cancel_url' => 'https://yourdomain.com/index.html',
```

### Step 3: Install Stripe PHP Library
On your server, run:
```bash
composer require stripe/stripe-php
```

### Step 4: Testing
- Use Stripe's test card: **4242 4242 4242 4242** (any future expiry, any CVC)
- Test mobile payments using Stripe's test credentials

## Payment Methods Supported
✅ Credit/Debit Cards (Visa, Mastercard, Amex)
✅ EFT Transfers (South Africa)
✅ Mobile Payments (Zapper, Snapscan, etc.)

## Pricing
- Current setup: **R150 per person**
- Adjust in `create-checkout-session.php` line 18: `$pricePerPerson = 150;`

## Production Mode
When going live:
1. Switch from test keys to live keys in Stripe Dashboard
2. Update all URLs to use `https://yourdomain.com`
3. Set up proper error handling and logging
4. Configure email notifications in Stripe Dashboard

## Security Notes
- Never commit your Secret Key to version control
- Use environment variables for storing keys
- Always validate inputs on the backend
- Keep Stripe SDK updated

## Support
For Stripe documentation: https://stripe.com/docs
