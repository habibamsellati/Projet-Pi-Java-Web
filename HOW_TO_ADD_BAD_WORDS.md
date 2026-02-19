# Quick Guide: How to Add Custom Bad Words

## Simple 3-Step Process

### Step 1: Edit the Configuration File
Open: `config/bad_words.yaml`

### Step 2: Add Your Words
```yaml
parameters:
  bad_words.custom_list:
    - 'lele'           # Already added
    - 'yourword'       # Add more words here
    - 'anotherword'    # One per line
```

### Step 3: Clear Cache
```bash
php bin/console cache:clear
```

## That's It!

Your custom words are now blocked in:
- Comments on articles
- Replies to comments  
- Reclamations (new and edit)

## Current Custom Words

You currently have these words blocked:
- `lele`

## Test It

Try submitting a comment with the word "lele" - it should show:
> ⚠️ Votre commentaire contient des mots inappropriés. Veuillez modifier votre message.

## Add More Words

To add more words, just add new lines in the same format:

```yaml
parameters:
  bad_words.custom_list:
    - 'lele'
    - 'spam'
    - 'scam'
    - 'arnaque'
    - 'publicité'
```

Remember to clear cache after each change!

## Important Notes

✅ Words are case-insensitive (blocks: lele, LELE, Lele)
✅ Matches whole words only (won't block "telegram" if you block "lele")
✅ Works in both French and English
✅ Checked BEFORE the API (faster)

For more details, see: `CUSTOM_BAD_WORDS_GUIDE.md`
