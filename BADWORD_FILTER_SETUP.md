# Bad Word Filter Setup Guide

## Installation ✅ COMPLETED

Symfony's HTTP Client component has been installed successfully:

```bash
composer require symfony/http-client
```

The bad word filter is now ready to use!

## What Was Added

### 1. BadWordFilterService
- **Location**: `src/Service/BadWordFilterService.php`
- **Purpose**: Server-side bad word detection using PurgoMalum API (free, no API key required)
- **Methods**:
  - `checkBadWords(string $text)`: Returns array with `hasBadWords` boolean
  - `getFilteredText(string $text)`: Returns text with bad words replaced by asterisks

### 2. Comment Validation
- **Files Modified**:
  - `src/Controller/ArticleController.php`
    - Added bad word check in `show()` method (new comments)
    - Added bad word check in `repondreCommentaire()` method (replies)
  - `templates/article/show.html.twig`
    - Added client-side validation for comments and replies
    - Shows "Vérification..." during validation
    - Prevents submission if bad words detected

### 3. Reclamation Validation
- **Files Modified**:
  - `src/Controller/ReclamationController.php`
    - Added bad word check in `new()` method
    - Added bad word check in `edit()` method
  - `templates/reclamation/new.html.twig`
    - Added client-side validation
  - `templates/reclamation/edit.html.twig`
    - Added client-side validation

## How It Works

### Server-Side (PHP)
1. User submits a comment or reclamation
2. BadWordFilterService calls PurgoMalum API
3. If bad words detected:
   - Flash error message: "⚠️ Votre [commentaire/réclamation] contient des mots inappropriés. Veuillez modifier votre message."
   - Form is re-rendered with user's input preserved
   - Submission is blocked
4. If no bad words, submission proceeds normally

### Client-Side (JavaScript)
1. User clicks submit button
2. JavaScript intercepts form submission
3. Calls PurgoMalum API directly from browser
4. If bad words detected:
   - Shows alert with warning message
   - Prevents form submission
   - Re-enables submit button
5. If no bad words, form submits normally

## API Used

**PurgoMalum** - Free profanity filter API
- URL: https://www.purgomalum.com/
- No API key required
- No rate limits for reasonable use
- Supports multiple languages
- Endpoints used:
  - `/service/containsprofanity` - Returns true/false
  - `/service/plain` - Returns filtered text

## Error Handling

- If API call fails (network error, timeout), the system "fails open" - allows submission
- This prevents blocking legitimate users due to API issues
- Server-side validation is primary, client-side is for better UX

## Testing

To test the bad word filter, try submitting:
- Comments with words like: "damn", "hell", "crap", etc.
- The API detects common profanity in English and other languages

## Customization

To add custom bad words or change behavior:
1. Edit `src/Service/BadWordFilterService.php`
2. Modify the API endpoint or add custom word lists
3. Adjust error messages in controllers and templates
