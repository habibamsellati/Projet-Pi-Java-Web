# Personalized Order Confirmation Email Feature

## Overview
When a client creates an order (commande), they automatically receive a personalized confirmation email with an AI-generated message.

## Features

### 1. AI-Generated Personalized Message
- Uses Hugging Face's free Mistral-7B model to generate unique messages
- Each message is personalized with:
  - Customer name
  - Order number
  - List of articles
  - Total amount
- Messages are warm, professional, and in French

### 2. Fallback System
- If the AI API is unavailable or slow, uses template-based messages
- Multiple message templates for variety
- Ensures emails are always sent even if AI fails

### 3. Email Content
The confirmation email includes:
- Personalized AI-generated greeting
- Order number
- Customer name
- Delivery address
- Payment method
- Complete list of ordered articles with prices
- Total amount
- Professional design with gradient header

## Technical Implementation

### Files Created/Modified

1. **src/Service/PersonalizedMessageService.php**
   - Service to generate personalized messages
   - Integrates with Hugging Face API (free tier)
   - Provides fallback templates

2. **src/Controller/PanierController.php**
   - Modified `valider()` method
   - Sends email after order creation
   - Handles errors gracefully (order still saved if email fails)

3. **templates/emails/order_confirmation.html.twig**
   - Beautiful HTML email template
   - Responsive design
   - Highlights personalized message

## How It Works

1. Client completes order form and submits
2. Order is saved to database
3. System generates personalized message using AI:
   - Tries Hugging Face API first
   - Falls back to templates if API fails
4. Email is sent with:
   - Personalized message
   - Order details
   - Article list
5. Client receives confirmation email
6. Client is redirected to order history

## Configuration

Email settings are in `.env`:
```
MAILER_DSN="smtp://sbaiemna04@gmail.com:atfalihgtypbftry@smtp.gmail.com:465"
MAIL_FROM=sbaiemna04@gmail.com
```

## API Used

- **Hugging Face Inference API** (Free tier)
  - Model: mistralai/Mistral-7B-Instruct-v0.2
  - No API key required for basic usage
  - Generates French text
  - 10 second timeout

## Error Handling

- If email sending fails, order is still saved
- Error is caught and logged silently
- User sees success message regardless
- This ensures order process is never blocked by email issues

## Testing

To test the feature:
1. Login as a client
2. Add articles to cart
3. Go to cart and click "Valider la commande"
4. Fill in the order form
5. Submit the order
6. Check the client's email inbox for confirmation

## Future Improvements

- Add order tracking link in email
- Include estimated delivery date
- Add company logo to email header
- Support multiple languages based on user preference
