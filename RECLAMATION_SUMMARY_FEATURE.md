# Réclamation Summary Feature

## Overview

Added a summary button to the backoffice reclamations list that displays a comprehensive summary of each reclamation in a modal popup.

## Features

### Summary Button
- Located next to each reclamation in the backoffice list
- Icon: 📊 Résumé
- Opens a modal with detailed information

### Summary Content

The summary includes:

1. **General Information**
   - Reclamation ID
   - Title
   - Status
   - Creation date
   - Processing time (if responses exist)

2. **User Information**
   - Full name
   - Email address
   - Role (Client/Artisan)

3. **Description**
   - Full reclamation description
   - Formatted with line breaks

4. **Responses** (if any)
   - Number of responses
   - Each response shows:
     - Date and time
     - Admin who responded
     - Response content

### Actions

- **Copy Summary**: Copies the entire summary as formatted text to clipboard
- **Close**: Closes the modal

### User Experience

- Modal opens with loading spinner
- Smooth animations
- Click outside modal to close
- Press ESC key to close
- Responsive design

## Technical Implementation

### Backend

**New Route**: `back_reclamation_summary`
- Path: `/admin/reclamation/{id}/summary`
- Method: GET
- Returns: JSON with summary data

**New Method**: `generateReclamationSummary()`
- Generates structured summary data
- Calculates processing time
- Formats text summary for copying

### Frontend

**Modal UI**:
- Fixed overlay with centered content
- Scrollable for long content
- Styled sections for better readability

**JavaScript Functions**:
- `showSummary(id)`: Fetches and displays summary
- `displaySummary(data)`: Renders summary in modal
- `closeSummary()`: Closes the modal
- `copySummary()`: Copies text to clipboard

## Files Modified

1. `src/Controller/BackController.php`
   - Added `reclamationSummary()` route method
   - Added `generateReclamationSummary()` helper method

2. `templates/admin/reclamations_back.html.twig`
   - Added summary button to each reclamation
   - Added modal HTML structure
   - Added CSS styles for modal
   - Added JavaScript for modal functionality

## Usage

1. Navigate to backoffice reclamations list
2. Click "📊 Résumé" button on any reclamation
3. View the comprehensive summary in the modal
4. Optionally copy the summary text
5. Close the modal when done

## Benefits

- Quick overview without navigating away
- Easy to copy and share information
- Shows processing time metrics
- All information in one place
- Better admin workflow efficiency
