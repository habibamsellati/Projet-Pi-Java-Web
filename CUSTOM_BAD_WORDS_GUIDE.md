# Custom Bad Words Configuration Guide

## How to Add Your Own Bad Words

You can easily add your own unaccepted words to the bad word filter by editing the configuration file.

### Step 1: Open the Configuration File

Edit the file: `config/bad_words.yaml`

### Step 2: Add Your Words

Add your custom bad words under the `bad_words.custom_list` parameter:

```yaml
parameters:
  bad_words.custom_list:
    - 'word1'
    - 'word2'
    - 'inappropriate_term'
    - 'spam'
```

**Important Format Rules:**
- Each word must start with `- '` and end with `'`
- Use proper indentation (2 spaces)
- Words are case-insensitive

### Step 3: Clear Cache

After adding words, clear the Symfony cache:

```bash
php bin/console cache:clear
```

## Examples

### French Bad Words
```yaml
parameters:
  bad_words.custom_list:
    - 'connard'
    - 'salaud'
    - 'merde'
    - 'idiot'
```

### English Bad Words
```yaml
parameters:
  bad_words.custom_list:
    - 'stupid'
    - 'idiot'
    - 'spam'
    - 'scam'
```

### Mixed Languages
```yaml
parameters:
  bad_words.custom_list:
    - 'spam'
    - 'scam'
    - 'arnaque'
    - 'publicité'
    - 'viagra'
```

## Important Notes

1. **Case Insensitive**: Words are matched case-insensitively
   - Adding 'spam' will block: spam, SPAM, Spam, SpAm

2. **Whole Words Only**: Words are matched as complete words
   - Adding 'bad' will block: "bad word"
   - But will NOT block: "badge" or "badminton"

3. **Priority**: Custom words are checked FIRST before the API
   - Faster response for your custom words
   - No API call needed if custom word is found

4. **No Restart Needed**: After clearing cache, changes take effect immediately

## How It Works

### Validation Flow:
1. User submits comment/reclamation
2. System checks custom bad words list (your words)
3. If found → Blocked with error message
4. If not found → Checks PurgoMalum API (standard profanity)
5. If found → Blocked with error message
6. If clean → Submission allowed

### Both Checks:
- **Custom List**: Your specific words (instant, no API call)
- **PurgoMalum API**: Standard profanity in multiple languages

## Testing Your Custom Words

1. Add a test word to `config/bad_words.yaml`:
```yaml
parameters:
  bad_words.custom_list:
    - 'testword'
```

2. Clear cache:
```bash
php bin/console cache:clear
```

3. Try to submit a comment with "testword"
4. You should see the error message blocking submission

## Example: Your Current Configuration

You currently have "lele" blocked:
```yaml
parameters:
  bad_words.custom_list:
    - 'lele'
```

Try submitting a comment with the word "lele" - it should be blocked!

## Troubleshooting

### Words Not Being Blocked?

1. **Check spelling**: Make sure the word is spelled correctly in the config file
2. **Clear cache**: Run `php bin/console cache:clear`
3. **Check YAML syntax**: Make sure indentation is correct (use spaces, not tabs)
4. **Whole word matching**: Remember it only matches complete words

### YAML Syntax Error?

Make sure your YAML is properly formatted:
- Use spaces for indentation (not tabs)
- Each word should start with `- '` and end with `'`
- Keep consistent indentation

**Correct:**
```yaml
parameters:
  bad_words.custom_list:
    - 'word1'
    - 'word2'
```

**Incorrect:**
```yaml
parameters:
  bad_words.custom_list:
  - word1
  - word2
```

**Also Incorrect (missing quotes and dash):**
```yaml
parameters:
  bad_words.custom_list:
    word1
    word2
```

## Advanced: Adding Words Programmatically

You can also add words dynamically in your code:

```php
// In a controller or service
$badWordFilter->addCustomBadWord('dynamic_word');
```

Note: Words added this way are only active for that request and won't persist.
