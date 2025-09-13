# User Onboarding API Documentation

> **Version:** 2.0
> **Base URLs:**
> - **Local Development:** `http://localhost:8000/api`
> - **Production:** `https://rest.lawexa.com/api`
> **Authentication:** Required (`Bearer {token}`)

## Overview

The onboarding system allows new users to complete their profile setup after registration. Users provide professional information including profession, location, areas of expertise, education, and work experience.

---

## Endpoints

### 1. Create/Update Profile

Complete user profile setup with professional and educational information.

**Endpoint:** `PUT /onboarding/profile`
**Authorization:** `Bearer {token}` (Required)

**Request Headers:**
```http
Authorization: Bearer 350|u0daxHM2dc8cdk9C6ftQKrYB9V4ReU4M96psXG8U8f4be19f
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "profession": "lawyer",
  "country": "Nigeria",
  "area_of_expertise": ["Criminal Law", "Corporate Law", "Family Law"],
  "work_experience": 5
}
```

**Validation Rules:**

| Field | Rules | Description |
|-------|-------|-------------|
| `profession` | required, string, max:100 | User's profession |
| `country` | required, string, max:100 | Country of residence |
| `area_of_expertise` | required, array, min:1, max:5 | Array of expertise areas |
| `area_of_expertise.*` | required, string, max:150 | Each expertise area |
| `university` | nullable, required_if:profession,student, string, max:200 | University name (students only) |
| `level` | nullable, required_if:profession,student, string, max:50 | Academic level (students only) |
| `work_experience` | nullable, integer, min:0, max:50 | Years of work experience |

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "id": 208,
      "name": "Test User",
      "email": "test@example.com",
      "role": "user",
      "avatar": null,
      "google_id": null,
      "customer_code": null,
      "email_verified": false,
      "subscription_status": "inactive",
      "subscription_expiry": null,
      "has_active_subscription": false,
      "subscriptions": [],
      "profession": "lawyer",
      "country": "Nigeria",
      "area_of_expertise": ["Criminal Law", "Corporate Law", "Family Law"],
      "university": null,
      "level": null,
      "work_experience": 5,
      "formatted_profile": "lawyer in Criminal Law, Corporate Law, Family Law (5 years experience) from Nigeria",
      "is_student": false,
      "is_lawyer": true,
      "is_law_student": false,
      "has_work_experience": true,
      "email_verified_at": null,
      "created_at": "2025-09-13T12:05:41.000000Z",
      "updated_at": "2025-09-13T12:06:03.000000Z"
    }
  }
}
```

### 2. Get Profile

Retrieve current user's profile information.

**Endpoint:** `GET /onboarding/profile`
**Authorization:** `Bearer {token}` (Required)

**Request Headers:**
```http
Authorization: Bearer 350|u0daxHM2dc8cdk9C6ftQKrYB9V4ReU4M96psXG8U8f4be19f
Accept: application/json
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Profile retrieved successfully",
  "data": {
    "user": {
      "id": 208,
      "name": "Test User",
      "profession": "student",
      "country": "Nigeria",
      "area_of_expertise": ["Law", "Political Science"],
      "university": "University of Lagos",
      "level": "300L",
      "work_experience": null,
      "formatted_profile": "student in Law, Political Science at University of Lagos from Nigeria",
      "is_student": true,
      "is_lawyer": false,
      "is_law_student": true,
      "has_work_experience": false
    }
  }
}
```

---

## Profile Examples

### Lawyer Profile
```json
{
  "profession": "lawyer",
  "country": "Nigeria",
  "area_of_expertise": ["Criminal Law", "Corporate Law"],
  "work_experience": 8
}
```

### Student Profile
```json
{
  "profession": "student",
  "country": "Nigeria",
  "area_of_expertise": ["Law", "Political Science"],
  "university": "University of Lagos",
  "level": "300L"
}
```

### Medical Doctor Profile
```json
{
  "profession": "doctor",
  "country": "Canada",
  "area_of_expertise": ["Cardiology", "Internal Medicine"],
  "work_experience": 12
}
```

### Multi-Disciplinary Professional
```json
{
  "profession": "consultant",
  "country": "United Kingdom",
  "area_of_expertise": ["Management", "Finance", "Digital Marketing", "Project Management"],
  "work_experience": 15
}
```

---

## Validation Examples

### Success Cases

**Single Area of Expertise:**
```json
{
  "profession": "teacher",
  "country": "Ghana",
  "area_of_expertise": ["Education"]
}
```

**Maximum Areas (5):**
```json
{
  "profession": "engineer",
  "country": "South Africa",
  "area_of_expertise": ["Software Engineering", "Data Science", "Machine Learning", "DevOps", "Cybersecurity"]
}
```

### Error Cases

**Too Many Areas (6+ areas):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "area_of_expertise": ["You can select up to 5 areas of expertise"]
  }
}
```

**Empty Areas Array:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "area_of_expertise": ["At least one area of expertise is required"]
  }
}
```

**Student Missing Required Fields:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "university": ["University is required for students"],
    "level": ["Academic level is required for students"]
  }
}
```

**Invalid Area Format:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "area_of_expertise.0": ["Each area of expertise must be a valid text"],
    "area_of_expertise.1": ["Each area of expertise cannot exceed 150 characters"]
  }
}
```

---

## Profile Features

### Helper Methods

The API automatically computes helpful boolean fields:

| Field | Description | Example Use Case |
|-------|-------------|------------------|
| `is_student` | Whether profession is "student" | Show student-specific features |
| `is_lawyer` | Whether profession is "lawyer" | Show legal content recommendations |
| `is_law_student` | Student with law-related area | Combine legal + academic features |
| `has_work_experience` | Has work experience > 0 | Show professional vs entry-level content |

### Formatted Profile

Auto-generated human-readable profile summary:

**Examples:**
- `"lawyer in Criminal Law, Corporate Law, Family Law (5 years experience) from Nigeria"`
- `"student in Law, Political Science at University of Lagos from Nigeria"`
- `"doctor in Cardiology from Canada"`
- `"consultant in Management, Finance and 4 other areas from UK"`

---

## Academic Levels

For students, the `level` field accepts various educational systems:

### Nigerian System
- `100L`, `200L`, `300L`, `400L`, `500L`, `600L`

### US System
- `Freshman`, `Sophomore`, `Junior`, `Senior`

### UK/General System
- `First Year`, `Second Year`, `Third Year`, `Final Year`

### Graduate Levels
- `Masters Year 1`, `Masters Year 2`
- `PhD Year 1`, `PhD Year 2`, `PhD Year 3`, `PhD Year 4+`

### General Categories
- `Undergraduate`, `Graduate`, `Postgraduate`
- `Diploma`, `Certificate`

---

## Error Handling

### Authentication Errors

**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Unauthenticated",
  "data": null
}
```

### Validation Errors

**422 Unprocessable Entity:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "field_name": [
      "Specific validation error message"
    ]
  }
}
```

---

## Usage Examples

### JavaScript/Fetch Example

```javascript
// Complete onboarding profile
async function completeOnboarding(profileData) {
  const baseUrl = process.env.NODE_ENV === 'production'
    ? 'https://rest.lawexa.com/api'
    : 'http://localhost:8000/api';

  const response = await fetch(`${baseUrl}/onboarding/profile`, {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer ' + userToken,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(profileData)
  });

  return await response.json();
}

// Example usage
const lawyerProfile = {
  profession: "lawyer",
  country: "Nigeria",
  area_of_expertise: ["Criminal Law", "Corporate Law", "Family Law"],
  work_experience: 5
};

const studentProfile = {
  profession: "student",
  country: "Nigeria",
  area_of_expertise: ["Law", "Political Science"],
  university: "University of Lagos",
  level: "300L"
};

completeOnboarding(lawyerProfile);
```

### cURL Examples

**Complete Lawyer Profile:**
```bash
curl -X PUT "http://localhost:8000/api/onboarding/profile" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "profession": "lawyer",
    "country": "Nigeria",
    "area_of_expertise": ["Criminal Law", "Corporate Law", "Family Law"],
    "work_experience": 5
  }'
```

**Complete Student Profile:**
```bash
curl -X PUT "http://localhost:8000/api/onboarding/profile" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "profession": "student",
    "country": "Nigeria",
    "area_of_expertise": ["Law", "Political Science"],
    "university": "University of Lagos",
    "level": "300L"
  }'
```

**Get Current Profile:**
```bash
curl -X GET "http://localhost:8000/api/onboarding/profile" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Integration with Reference Data

### Step 1: Fetch Available Options
```javascript
// Get all available areas of expertise
const areasResponse = await fetch('/api/reference/areas-of-expertise');
const { data: { areas } } = await areasResponse.json();

// Get countries
const countriesResponse = await fetch('/api/reference/countries');
const { data: { countries } } = await countriesResponse.json();

// Get academic levels (for students)
const levelsResponse = await fetch('/api/reference/levels');
const { data: { levels } } = await levelsResponse.json();
```

### Step 2: Build Dynamic Form
```javascript
// Build expertise selection (multi-select with max 5)
const expertiseSelect = areas.map(area => ({
  value: area,
  label: area
}));

// Build country dropdown
const countrySelect = countries.map(country => ({
  value: country.country,
  label: country.country
}));

// Build level dropdown (show only if profession is "student")
const levelSelect = levels.map(level => ({
  value: level.value,
  label: level.label
}));
```

---

## Notes

- **Email Verification**: Profile creation may require email verification
- **Required Fields**: Profession, country, and at least one area of expertise are required
- **Student Fields**: University and level become required when profession is "student"
- **Area Limits**: Users can select 1-5 areas of expertise from dynamically available options
- **Reference Data**: Use `/api/reference/*` endpoints to populate form options
- **Profile Updates**: Can be called multiple times to update profile information
- **Backward Compatibility**: Existing single-area profiles automatically work with arrays

---

## Related Endpoints

- [Authentication](authentication.md) - User registration and login
- [Profile Management](profile-management.md) - View and update full profile
- [Reference Data](reference-data.md) - Available countries, areas, universities
- [User Dashboard](dashboard.md) - Post-onboarding user experience