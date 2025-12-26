# FloraFinder Development Roadmap: addressing Partial Implementations

This document outlines the technical steps required to fully realize the goals set out in the project abstract by addressing the identified partial implementations.

---

## 1. Structured Habitat and Life Cycle Data
**Goal:** Extract "Habitat" and "Life Cycle" into dedicated database fields to allow for structured filtering and cleaner UI displays.

### Implementation Steps:
1.  **Update DB Migration:** Ensure columns `habitat` and `lifespan` (already in model) are properly used in the `plants` table.
2.  **Enhance `CareDetailsService`:**
    *   Update the Gemini prompt in `callGeminiAPI` to specifically request `habitat` (e.g., "primary biome, altitude range") and `lifespan` (e.g., "annual, perennial, duration in years").
    *   Map these new JSON keys to the `storeCareDetails` method to save them in the `plants` table.
3.  **Update UI (`Show.vue`):** 
    *   Add a "Growth & Habitat" section in the plant details page to display these specific values instead of burying them in the general description.

---

## 2. Professional "Research-Oriented" Tools
**Goal:** Provide researchers with the ability to export data and ensure high data quality through expert verification.

### Implementation Steps:
1.  **Sighting Verification:**
    *   Add an `is_verified` boolean to the `sightings` table.
    *   Create an Admin UI where users with the `expert` or `admin` role can review public sightings and mark them as "Verified Species."
2.  **Data Export System:**
    *   Implement a tool (like `Maatwebsite/Laravel-Excel`) to allow admins/researchers to export sighting data as **CSV** or **JSON**.
    *   Include fields like Scientific Name, Coordinates, Region, and Timestamp for ecological mapping.

---

## 3. Active Environmental Preservation
**Goal:** Transition from passive reporting to active conservation alerts for endangered Malaysian flora.

### Implementation Steps:
1.  **Automated Alerts:**
    *   Create a `SightingObserver`.
    *   When a sighting is saved, if the `iucn_category` is `CR` (Critically Endangered) or `EN` (Endangered), trigger a `HighPrioritySighting` event.
2.  **Agency Notifications:**
    *   Configure a mailing list for local conservation bodies (e.g., PERHILITAN).
    *   Automatically send a high-detail report (photo + GPS) when a rare species is spotted in the wild.

---

## 4. "Real-Time" Mobile Optimization
**Goal:** Improve the user experience on-site by reducing friction and providing a native-app feel.

### Implementation Steps:
1.  **Progressive Web App (PWA):**
    *   Use `vite-plugin-pwa` to enable offline support and a "Home Screen" shortcut for gardeners and researchers in remote areas.
2.  **Direct Camera Access:**
    *   In `UploadPanel.vue`, use the HTML `capture="environment"` attribute on the image input to force mobile devices to open the camera directly instead of the gallery.
    *   Implement a "Live Preview" mode using the `Stream API` for instant feedback on image quality before capture.

---

## 5. Localized Malaysian Biodiversity Knowledge
**Goal:** Ensure the AI results are contextually accurate for the Malaysian environment and cultural context.

### Implementation Steps:
1.  **State-Specific Knowledge:**
    *   Update the `CareDetailsService` to include the user's current Malaysian state (Sabah, Sarawak, etc.) in the AI prompt to get localized advice (e.g., "In the humid climate of Sarawak, ensure...").
2.  **Traditional/Local Names:** 
    *   Continue refining the `malay_name` extraction and include "Traditional Uses" as a field, focusing on Malaysian ethno-botany.
3.  **Native vs Invasive:** 
    *   Explicitly prompt the AI to determine if a species is **Invasive** in the Malaysian ecosystem, helping gardeners remove harmful species like *Mimosa pigra*.
