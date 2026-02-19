# Features Added Summary

## 1. Google Translate for Comments ✅

### What was added:
- Translation button on each comment with language selector
- Support for 7 languages: French, English, Spanish, German, Italian, Arabic, Chinese
- Toggle between translated and original text
- Visual feedback with blue background for translated comments
- Uses MyMemory Translation API (free, no API key needed)

### Files modified:
- `templates/article/show.html.twig`
  - Added translate controls UI
  - Added JavaScript translation function
  - Added CSS styling for translated comments

### How to use:
1. Go to any article page with comments
2. Select target language from dropdown
3. Click "🌐 Traduire" button
4. Click "↩️ Original" to revert

---

## 2. Bad Word Filter ✅

### What was added:
- Server-side validation using PurgoMalum API
- Client-side validation for instant feedback
- Blocks submission if inappropriate words detected
- Works for both comments and reclamations

### Files created:
- `src/Service/BadWordFilterService.php` - Service for bad word detection

### Files modified:
- `src/Controller/ArticleController.php`
  - Added validation in `show()` method (comments)
  - Added validation in `repondreCommentaire()` method (replies)
- `src/Controller/ReclamationController.php`
  - Added validation in `new()` method
  - Added validation in `edit()` method
- `templates/article/show.html.twig`
  - Added client-side validation for comments
  - Added client-side validation for replies
- `templates/reclamation/new.html.twig`
  - Added client-side validation
- `templates/reclamation/edit.html.twig`
  - Added client-side validation

### How it works:
1. User types comment/reclamation
2. On submit, client-side check runs first (instant feedback)
3. If passes, server-side check validates again (security)
4. If bad words detected: Shows error message and blocks submission
5. If clean: Submission proceeds normally

### Error message shown:
"⚠️ Votre [commentaire/réclamation] contient des mots inappropriés. Veuillez modifier votre message."

---

## Installation ✅ COMPLETED

The required dependency has been installed:

```bash
composer require symfony/http-client
```

Both features are now fully functional and ready to use!

---

## APIs Used

1. **MyMemory Translation API**
   - Free translation service
   - No API key required
   - Endpoint: `https://api.mymemory.translated.net/get`

2. **PurgoMalum API**
   - Free profanity filter
   - No API key required
   - Endpoint: `https://www.purgomalum.com/service/containsprofanity`

---

## Testing

### Test Translation:
1. Navigate to an article with comments
2. Try translating a comment to different languages
3. Toggle back to original

### Test Bad Word Filter:
1. Try submitting a comment with words like "damn", "hell", "crap"
2. Should see error message and submission blocked
3. Edit to remove bad words and submit successfully

---

## Notes

- Both features work without requiring API keys
- Bad word filter "fails open" - if API is down, submissions are allowed
- Translation is client-side only, original text is never modified
- Bad word checking happens both client-side (UX) and server-side (security)
