# Save to Database Feature

## Overview
Users can now save their identified plants to the database directly from the identification results page. The save function stores the selected plant match along with all its metadata and location information.

## Key Features

### 1. **Save Button in Results**
- Located in the action buttons section of the identification results
- Only appears after successful plant identification
- Shows the currently selected plant match from all possibilities
- Visual states:
  - Default: "Save to Database" with database icon
  - Loading: "Saving..." with spinning loader
  - Saved: "Saved to Database" with check icon (disabled)

### 2. **Saved Data**
When a user clicks "Save to Database", the following information is stored:

#### Image Data
- Original uploaded image
- File path and URL
- Filename, MIME type, and size
- Plant organ type (flower, leaf, fruit, etc.)

#### Plant Identification Data
- Scientific name (with and without author)
- Common name
- Family and Genus
- Confidence score
- GBIF ID (if available)
- POWO (Kew Science) ID (if available)
- IUCN conservation category (if available)

#### Location Data (if included)
- Location name
- Region (Malaysian states)
- GPS coordinates (latitude/longitude)

### 3. **User Experience**

#### Before Identification
- User uploads plant image
- Can optionally include location data via:
  - EXIF data from photo (automatic)
  - Current location button
  - Manual entry

#### During Identification
- AI processes the image
- Returns multiple possible matches
- User can browse through alternatives

#### After Identification
- User reviews the matches
- Selects the most accurate one
- Clicks "Save to Database" button
- System saves the **selected plant** (not all matches)

#### Success Flow
```
User identifies plant
  ↓
Views multiple matches
  ↓
Selects best match (e.g., match #2)
  ↓
Clicks "Save to Database"
  ↓
System saves match #2 with all metadata
  ↓
Button changes to "Saved to Database" (disabled)
  ↓
Success toast appears
```

## Technical Implementation

### Frontend (Vue 3 + TypeScript)

#### State Variables
```typescript
const savingToDatabase = ref<boolean>(false);
const selectedResultIndex = ref<number>(0); // Tracks which match is selected
```

#### Save Function
```typescript
const savePlantToDatabase = async () => {
  // Validates data exists
  // Creates FormData with selected plant info
  // Includes all metadata and location data
  // Sends POST request to backend
  // Updates UI based on response
}
```

#### API Call
- **Endpoint**: `POST /plant-identifier/save`
- **Method**: `router.post()` (Inertia.js)
- **Data**: FormData with multipart/form-data

### Backend (Laravel)

#### Route
```php
Route::post(
    '/plant-identifier/save',
    [PlantIdentifierController::class, 'save']
)->name('plant-identifier.save');
```

#### Controller Method
```php
public function save(Request $request)
{
    // Validates all required fields
    // Saves image to storage
    // Logs plant data (mock database)
    // Returns success/error response
}
```

#### Validation Rules
- Image: required, max 10MB
- Plant data: scientific name, family, genus, confidence
- Location data: optional GPS coordinates and name
- External IDs: optional GBIF, POWO, IUCN

#### Storage
- Images saved to: `storage/app/public/plant-identifications/`
- Accessible via: `storage/plant-identifications/{filename}`

### Mock Database Save
Currently implemented as a **mock** for demonstration:
```php
private function saveToDatabase($imageFile, $organ, $plantData = [])
{
    // Saves image to storage
    // Creates data array with all plant info
    // Logs to Laravel log file
    // TODO: Replace with actual database model
}
```

## Data Flow

### Request Structure
```json
{
  "image": File,
  "organ": "flower",
  "saveToDatabase": "1",
  "scientificName": "Hibiscus rosa-sinensis L.",
  "scientificNameWithoutAuthor": "Hibiscus rosa-sinensis",
  "commonName": "Chinese Hibiscus",
  "family": "Malvaceae",
  "genus": "Hibiscus",
  "confidence": "0.95",
  "gbifId": "123456",
  "powoId": "789012",
  "iucnCategory": "LC",
  "locationName": "Kuala Lumpur Botanical Garden",
  "region": "Selangor",
  "latitude": "3.1466",
  "longitude": "101.6958"
}
```

### Response Structure
```json
{
  "success": true,
  "message": "Plant saved successfully",
  "data": {
    "path": "plant-identifications/xyz.jpg",
    "url": "/storage/plant-identifications/xyz.jpg",
    "scientific_name": "Hibiscus rosa-sinensis L.",
    "uploaded_at": "2025-10-09 14:30:00"
  }
}
```

## UI/UX Design

### Save Button Styling
```vue
<Button
  variant="default"
  class="bg-moss-600 hover:bg-moss-700 shadow-md"
  :disabled="savingToDatabase || results?.savedToDatabase"
>
```

- **Primary action**: Green/moss color
- **Prominent placement**: First in action buttons row
- **Icon**: Database icon for clarity
- **States**: Normal, Loading, Disabled (saved)

### Toast Notifications
1. **Saving**: "Plant identification saved to database"
2. **Success**: "Plant Saved Successfully!"
3. **Error**: "Unable to save plant to database"

## Important Behaviors

### 1. **Saves Selected Match Only**
- If user switches between matches (match #1, #2, #3)
- System saves whichever match is currently selected
- User has full control over which identification to save

### 2. **One-Time Save**
- After saving, button is disabled
- Shows "Saved to Database" with check icon
- Prevents duplicate saves
- State persists during session

### 3. **Location Optional**
- Location data is **not required** to save
- If user didn't enable location, saves without it
- EXIF data is automatically included if found

### 4. **Error Handling**
- Network errors: Shows error toast, allows retry
- Validation errors: Displays specific error message
- Missing data: Validates before attempting save

## Future Enhancements

### Database Integration
Currently a **mock implementation**. To integrate with real database:

1. **Create Migration**
```php
Schema::create('plant_identifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->string('image_path');
    $table->string('scientific_name');
    $table->string('common_name')->nullable();
    $table->string('family');
    $table->string('genus');
    $table->decimal('confidence', 5, 4);
    $table->string('organ');
    $table->string('gbif_id')->nullable();
    $table->string('powo_id')->nullable();
    $table->string('iucn_category')->nullable();
    $table->string('location_name')->nullable();
    $table->string('region')->nullable();
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    $table->timestamps();
});
```

2. **Create Model**
```php
class PlantIdentification extends Model
{
    protected $fillable = [
        'user_id', 'image_path', 'scientific_name',
        'common_name', 'family', 'genus', 'confidence',
        'organ', 'gbif_id', 'powo_id', 'iucn_category',
        'location_name', 'region', 'latitude', 'longitude'
    ];
}
```

3. **Update Controller**
```php
// Replace in saveToDatabase method:
PlantIdentification::create($savedData);
```

### Additional Features
1. **User Authentication**
   - Associate saves with logged-in users
   - View personal identification history
   - Edit or delete saved identifications

2. **Duplicate Detection**
   - Check if user already saved this plant
   - Warn before saving duplicates
   - Merge duplicate entries

3. **Batch Save**
   - Save multiple matches at once
   - Export saved data as CSV/PDF
   - Sync to cloud storage

4. **Social Features**
   - Share saved identifications
   - Comment on identifications
   - Rate accuracy of identifications

5. **Analytics**
   - Track most identified plants
   - Heatmap of plant sightings
   - Seasonal trends

## Testing

### Test Scenarios

1. **Happy Path**
   - Upload image → Identify → Select match → Save
   - Verify success toast
   - Verify button state changes

2. **With Location**
   - Upload image with EXIF data
   - Identify plant
   - Save with location included
   - Verify location saved in logs

3. **Without Location**
   - Upload image without location
   - Identify plant
   - Save without location
   - Verify save still works

4. **Different Matches**
   - Identify plant with multiple matches
   - Select match #2
   - Save
   - Verify match #2 data is saved (not match #1)

5. **Error Cases**
   - Network timeout
   - Invalid image
   - Server error
   - Verify error handling

### Verification
Check Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "Plant identification saved"
```

Check uploaded files:
```bash
ls -lh storage/app/public/plant-identifications/
```

## API Documentation

### POST /plant-identifier/save

**Authentication**: Required (future)

**Request**:
- Method: POST
- Content-Type: multipart/form-data

**Parameters**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| image | File | Yes | Plant image (max 10MB) |
| organ | String | Yes | Plant part (flower/leaf/fruit/bark/habit/other) |
| saveToDatabase | Boolean | Yes | Must be true/1 |
| scientificName | String | Yes | Full scientific name with author |
| scientificNameWithoutAuthor | String | Yes | Scientific name without author |
| commonName | String | No | Common/vernacular name |
| family | String | Yes | Taxonomic family |
| genus | String | Yes | Taxonomic genus |
| confidence | Number | Yes | Confidence score (0-1) |
| gbifId | String | No | GBIF database ID |
| powoId | String | No | Kew Science POWO ID |
| iucnCategory | String | No | IUCN conservation status |
| locationName | String | No | Location description |
| region | String | No | Malaysian region/state |
| latitude | Number | No | GPS latitude (-90 to 90) |
| longitude | Number | No | GPS longitude (-180 to 180) |

**Response**:
- Success: 302 redirect with success flash message
- Error: 302 redirect with error flash message

## Security Considerations

### Input Validation
- Image size limited to 10MB
- File type restricted to images
- GPS coordinates validated (valid ranges)
- String lengths limited (max 255 chars)

### Storage Security
- Images stored in `public` disk (accessible)
- Consider moving to private disk for sensitive data
- Implement access control for saved identifications

### Future Security
- CSRF protection (already enabled in Laravel)
- Rate limiting on save endpoint
- User authentication required
- Input sanitization for XSS prevention

## Resources
- [Laravel File Storage](https://laravel.com/docs/filesystem)
- [Inertia.js Form Handling](https://inertiajs.com/forms)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
