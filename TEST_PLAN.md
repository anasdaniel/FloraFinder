# FloraFinder Test Plan

## 1. Introduction
This document outlines the comprehensive test plan for the FloraFinder application. The goal is to ensure all modules—Authentication, Dashboard, Plant Identification, Plant Library, Sightings, Forum, and Administration—function correctly and perform efficiently under various conditions.

## 2. Test Strategy
*   **Unit Testing**: Verified via PHPUnit for backend logic (Models, Services).
*   **Feature Testing**: End-to-end user flows tested manually or via tools like Cypress/Dusk.
*   **Device Testing**: Responsive design validation on Desktop (Chrome/Safari) and Mobile.

---

## 3. Test Cases by Module

### 3.1 Authentication & User Management
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **AUTH-01** | User Registration | 1. Go to `/register`.<br>2. Enter valid Name, Email, Password.<br>3. Submit. | Account created, redirected to Dashboard, verification email sent. |
| **AUTH-02** | Login | 1. Go to `/login`.<br>2. Enter correct credentials.<br>3. Submit. | Redirected to Dashboard. Session active. |
| **AUTH-03** | Invalid Login | 1. Enter incorrect password. | Error message displayed. No redirect. |
| **AUTH-04** | Forgot Password | 1. Go to `/forgot-password`.<br>2. Enter email. | Password reset link sent if email exists. |
| **AUTH-05** | Profile Update | 1. Navigate to `/settings/profile`.<br>2. Change Name/Email/Avatar.<br>3. Save. | Profile banner and sidebar reflect changes immediately. |
| **AUTH-06** | Delete Account | 1. Navigate to Settings -> Delete Account.<br>2. Confirm. | Account and all associated data (sightings, posts) removed. Redirect to login. |

### 3.2 Dashboard
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **DASH-01** | Statistics Rendering | 1. Load `/dashboard`. | Total Sightings, Unique Species, and Activity stats match DB counts. |
| **DASH-02** | Activity Chart | 1. Toggle "Last 6 Months" dropdown. | Chart updates via API fetch to show correct data points. |
| **DASH-03** | Map Rendering | 1. Check "Recent Discovery Map" widget. | Leaflet/Google map loads. Latest sighting marker is centered. |
| **DASH-04** | Seasonal Alerts | 1. Verify "Seasonal Alerts" card. | Displays active fruit/flower seasons based on current month. |

### 3.3 Plant Identifier (AI)
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **ID-01** | Image Upload | 1. Go to `/plant-identifier`.<br>2. Upload JPG/PNG < 5MB. | Preview shown. "Identify" button enabled. |
| **ID-02** | AI Identification | 1. Click "Identify". | Loading state shown. Results (Scientific Name, Confidence, Description) displayed. |
| **ID-03** | Chat with Botanist | 1. After identification, type a question in chat box. | AI responds with context-aware answer about the plant. |
| **ID-04** | Save to Collection | 1. Click "Save to My Plants". | Plant saved to "My Plants". Redirects to collection. |

### 3.4 Plant Library
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **LIB-01** | Search & Filter | 1. Go to `/plants`.<br>2. Search "Fern".<br>3. Filter "Endangered". | List updates to show matching plants. URL parameters update. |
| **LIB-02** | View Details | 1. Click a plant card. | Navigate to `/plants/{id}`. Shows Details, Taxonomy, Care Guide. |
| **LIB-03** | Care Guide Refresh | 1. Click "Refresh Care Info" (if admin/available). | Fetches new data from Gemini/Trefle. Page reloads with data. |

### 3.5 Sightings & My Plants
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **SIGHT-01** | Create Sighting | 1. From Identifier result or Dashboard, click "Report Sighting".<br>2. Fill details (Location, Date). | Sighting saved. appears in "My Plants" and Public Map. |
| **SIGHT-02** | Visual Map | 1. Go to `/plant-map`. | Map loads with markers for all user's sightings. Clicking marker shows popup. |
| **SIGHT-03** | Public Map | 1. Go to `/sightings-map`. | Shows markers from ALL users. Pagination works correctly. |
| **SIGHT-04** | Delete Sighting | 1. Go to `/sightings/{id}`.<br>2. Click Delete. | Record removed. Redirects to list. |

### 3.6 Community Forum
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **FORUM-01** | Thread Creation | 1. Go to `/forum/new`.<br>2. Enter Title, Content, Tags.<br>3. Post. | Thread created. Redirects to thread view. |
| **FORUM-02** | Commenting | 1. Open a thread.<br>2. Write comment.<br>3. Submit. | Comment appears instantly (via optimistic UI or fetch). |
| **FORUM-03** | Tagging System | 1. Click "Add Tag".<br>2. Search "Help".<br>3. Select tag. | Tag added to thread header. Tag count updates. |
| **FORUM-04** | Liking | 1. Click Heart icon on thread. | Heart turns red. Count increments. Persists on reload. |
| **FORUM-05** | Filtering | 1. Click "Identification" category tab. | Only identification requests shown. |

### 3.7 Admin Panel (Authorized Users Only)
| ID | Test Case | Steps | Expected Result |
| :--- | :--- | :--- | :--- |
| **ADMIN-01** | Access Control | 1. Log in as regular user.<br>2. Try accessing `/admin/dashboard`. | 403 Forbidden. |
| **ADMIN-02** | User Management | 1. As Admin, go to `/admin/users`.<br>2. Toggle "Admin" on a user. | User role updated. |
| **ADMIN-03** | Content Moderation | 1. Go to `/admin/forum`.<br>2. Delete a reported thread. | Thread removed from public forum. |

## 4. Performance & Security Checks
*   [ ] **N+1 Query Check**: Verify no lazy loading loops in lists (Plants, Forum, Sightings).
*   [ ] **Image Optimization**: Ensure images on `/plants` and `/forum` have `loading="lazy"`.
*   [ ] **Input Validation**: Try submitting empty forms or XSS scripts `<script>alert(1)</script>` in Forum.
*   [ ] **Console Errors**: Check DevTools console for 404s or JS errors on main pages.

## 5. Acceptance Criteria
*   All Critical (P0) test cases must pass.
*   No visual regressions in the Sidebar, Navbar, or Mobile layout.
*   Page load speed under 1.5s on local dev environment.
