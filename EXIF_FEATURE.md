# EXIF Geolocation Detection Feature

## Overview
The Plant Identifier now automatically extracts GPS location data from uploaded photos using EXIF (Exchangeable Image File Format) metadata.

## How It Works

### Automatic Detection
When a user uploads a plant photo:
1. The system automatically reads the EXIF metadata from the image
2. Extracts GPS coordinates (latitude and longitude) if available
3. Displays the location on the form
4. Uses reverse geocoding to get a human-readable location name
5. Shows visual feedback throughout the process

### User Experience Flow

#### 1. **Image Upload**
- User uploads or drops an image
- Loading indicator appears: "Reading location data..."

#### 2. **Success Scenarios**

**With GPS Data:**
- Toast notification: "Location Found in Photo!"
- Shows coordinates: `GPS Coordinates: 3.123456, 101.654321`
- If available, shows photo date: `(Photo taken: 1/15/2025)`
- Green badge appears: "Location detected from photo EXIF data"
- Location form fields are automatically filled
- `Include Location` checkbox is auto-enabled

**Without GPS Data:**
- Toast notification: "No Location Data"
- Message: "This photo doesn't contain GPS information. You can add location manually or use current location."
- User can still manually add location

#### 3. **Error Handling**
- If EXIF reading fails, shows friendly message
- Doesn't block the upload process
- User can continue without location data

### Technical Implementation

#### Dependencies
```json
{
  "exifr": "^7.1.3"
}
```

#### Key Functions

**`extractExifData(file: File)`**
- Parses EXIF data using the `exifr` library
- Extracts GPS coordinates with 6 decimal precision
- Calls reverse geocoding for location name
- Logs camera info and timestamp for debugging

**`reverseGeocode(latitude: number, longitude: number)`**
- Uses OpenStreetMap Nominatim API
- Converts coordinates to human-readable address
- Determines appropriate region (e.g., Peninsular Malaysia, Sabah, Sarawak)
- Falls back gracefully if reverse geocoding fails

#### Data Extracted
- **GPS Coordinates:** Latitude and Longitude
- **Date/Time:** When the photo was taken
- **Camera Info:** Make and Model (logged for debugging)

### Visual Indicators

#### Loading State
```vue
<div v-if="extractingExif" class="...">
  <Icon name="loader-2" class="animate-spin" />
  <span>Reading location data...</span>
</div>
```

#### Success Badge
```vue
<div v-if="imagePreview && form.latitude && form.longitude">
  <Icon name="map-pin" />
  <span>Location detected from photo EXIF data</span>
</div>
```

### Privacy & Security

#### What's Stored
- GPS coordinates are kept **locally in the form state**
- Currently NOT sent to the backend (prototype phase)
- Users are informed via toast when location data is saved

#### User Control
- Users can see when location is detected
- Can manually override detected location
- Can clear location data by resetting the form
- Location data is optional (checkbox control)

### Supported Image Formats
- JPEG/JPG (most common for photos with EXIF)
- PNG (limited EXIF support)
- GIF (limited EXIF support)

### Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Uses standard File API and blob reading
- Graceful degradation for unsupported browsers

## Testing

### Test Cases

1. **Photo with GPS Data**
   - Upload a photo taken with GPS-enabled smartphone
   - Should extract coordinates and show success toast
   - Should display green badge

2. **Photo without GPS Data**
   - Upload a screenshot or computer-generated image
   - Should show "No Location Data" message
   - Should allow manual entry

3. **Corrupted/Invalid File**
   - Upload an invalid image
   - Should handle gracefully with error message
   - Should not block the upload process

4. **Drag & Drop**
   - Drag a photo with GPS data into the upload area
   - Should extract EXIF data automatically
   - Same behavior as click upload

### Mock GPS Data for Testing
For testing without real photos, you can create images with EXIF data using:
- **ExifTool** (command line)
- **Online EXIF editors**
- Photos from GPS-enabled smartphones/cameras

## Future Enhancements

### Planned Features
1. **Backend Integration**
   - Send location data to server for storage
   - Associate with plant identification records

2. **Enhanced Metadata**
   - Extract altitude/elevation
   - Extract compass direction
   - Extract weather data (if available)

3. **Privacy Settings**
   - Strip EXIF data before sharing
   - Option to disable auto-detection
   - Blur location precision

4. **Advanced Geocoding**
   - Use Google Maps API for better accuracy
   - Show nearby landmarks
   - Suggest known plant habitats

5. **Location History**
   - Track sighting locations over time
   - Visualize on interactive map
   - Generate heat maps of plant distribution

## API Reference

### OpenStreetMap Nominatim
```
https://nominatim.openstreetmap.org/reverse?
  format=json&
  lat={latitude}&
  lon={longitude}&
  addressdetails=1
```

**Rate Limits:** 1 request per second
**Alternative:** Consider upgrading to paid service (Google Maps, Mapbox) for production

## Resources
- [EXIFR Documentation](https://github.com/MikeKovarik/exifr)
- [EXIF Standard](https://www.exif.org/)
- [OpenStreetMap Nominatim](https://nominatim.org/)
- [GPS Coordinate Precision](https://en.wikipedia.org/wiki/Decimal_degrees)
