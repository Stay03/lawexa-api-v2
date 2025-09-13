# Reference Data API Documentation

> **Version:** 2.0
> **Base URLs:**
> - **Local Development:** `http://localhost:8000/api`
> - **Production:** `https://rest.lawexa.com/api`
> **Authentication:** Required (`Bearer {token}`)

## Overview

Reference data endpoints provide dropdown options and structured data for user profile creation, search filters, and form population. The professions and areas of expertise endpoints return dynamic data sourced from actual user profiles, ensuring relevant and up-to-date options that grow organically with your user base. All endpoints require authentication and support the complete user onboarding and profile management workflow.

---

## Endpoints

### 1. Areas of Expertise

Dynamic list of professional areas sourced from actual user profiles. This data grows organically as users register and includes case-insensitive consolidation (e.g., "Lawyer", "lawyer", "LAWYER" appear as single "Lawyer" entry).

**Endpoint:** `GET /reference/areas-of-expertise`
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
  "message": "Areas of expertise retrieved successfully",
  "data": {
    "areas": [
      "Civil Law",
      "Corporate Law",
      "Law",
      "Political Science"
    ]
  }
}
```

**Note:** The above shows actual sample data from the user database. Your response will contain the unique areas of expertise that users have entered in their profiles, with case variations automatically consolidated (e.g., "civil law", "Civil Law", "CIVIL LAW" all appear as "Civil Law").

**Dynamic Data Features:**
- **User-Driven Content**: Areas are sourced from actual user registrations
- **Case Consolidation**: Automatically handles case variations ("Law", "law", "LAW" → "Law")
- **Growing Dataset**: New areas appear as users register with different expertise
- **Alphabetical Sorting**: Results are automatically sorted for consistent UI display
- **Quality Filtering**: Empty strings and invalid entries are automatically filtered out

---

### 2. Countries

List of countries with country codes for user location data.

**Endpoint:** `GET /reference/countries`
**Authorization:** `Bearer {token}` (Required)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Countries retrieved successfully",
  "data": {
    "countries": [
      {
        "country_code": "CA",
        "country": "Canada"
      },
      {
        "country_code": "NG",
        "country": "Nigeria"
      },
      {
        "country_code": "US",
        "country": "United States"
      },
      {
        "country_code": "GB",
        "country": "United Kingdom"
      }
    ]
  }
}
```

---

### 3. Universities

Searchable list of universities with country filtering.

**Endpoint:** `GET /reference/universities`
**Authorization:** `Bearer {token}` (Required)

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `country` | string | Filter by country name |
| `search` | string | Search university names |

**Examples:**
- `GET /reference/universities?country=Nigeria`
- `GET /reference/universities?search=Lagos`
- `GET /reference/universities?country=Nigeria&search=University`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Universities retrieved successfully",
  "data": {
    "universities": [
      {
        "id": 1,
        "name": "University of Lagos",
        "country_code": "NG",
        "country": "Nigeria",
        "website": "https://unilag.edu.ng"
      },
      {
        "id": 2,
        "name": "University of Ibadan",
        "country_code": "NG",
        "country": "Nigeria",
        "website": "https://ui.edu.ng"
      },
      {
        "id": 3,
        "name": "Harvard University",
        "country_code": "US",
        "country": "United States",
        "website": "https://harvard.edu"
      }
    ]
  }
}
```

---

### 4. Academic Levels

Comprehensive list of academic levels supporting multiple educational systems.

**Endpoint:** `GET /reference/levels`
**Authorization:** `Bearer {token}` (Required)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Academic levels retrieved successfully",
  "data": {
    "levels": [
      {
        "value": "100L",
        "label": "100 Level (1st Year)"
      },
      {
        "value": "200L",
        "label": "200 Level (2nd Year)"
      },
      {
        "value": "300L",
        "label": "300 Level (3rd Year)"
      },
      {
        "value": "400L",
        "label": "400 Level (4th Year)"
      },
      {
        "value": "500L",
        "label": "500 Level (5th Year)"
      },
      {
        "value": "600L",
        "label": "600 Level (6th Year)"
      },
      {
        "value": "Freshman",
        "label": "Freshman (1st Year)"
      },
      {
        "value": "Sophomore",
        "label": "Sophomore (2nd Year)"
      },
      {
        "value": "Junior",
        "label": "Junior (3rd Year)"
      },
      {
        "value": "Senior",
        "label": "Senior (4th Year)"
      },
      {
        "value": "First Year",
        "label": "First Year"
      },
      {
        "value": "Second Year",
        "label": "Second Year"
      },
      {
        "value": "Third Year",
        "label": "Third Year"
      },
      {
        "value": "Final Year",
        "label": "Final Year"
      },
      {
        "value": "Masters Year 1",
        "label": "Masters - Year 1"
      },
      {
        "value": "Masters Year 2",
        "label": "Masters - Year 2"
      },
      {
        "value": "PhD Year 1",
        "label": "PhD - Year 1"
      },
      {
        "value": "PhD Year 2",
        "label": "PhD - Year 2"
      },
      {
        "value": "PhD Year 3",
        "label": "PhD - Year 3"
      },
      {
        "value": "PhD Year 4+",
        "label": "PhD - Year 4+"
      },
      {
        "value": "Undergraduate",
        "label": "Undergraduate (General)"
      },
      {
        "value": "Graduate",
        "label": "Graduate (General)"
      },
      {
        "value": "Postgraduate",
        "label": "Postgraduate"
      },
      {
        "value": "Diploma",
        "label": "Diploma Program"
      },
      {
        "value": "Certificate",
        "label": "Certificate Program"
      }
    ]
  }
}
```

**Systems Supported:**
- **Nigerian**: 100L-600L format
- **US**: Freshman, Sophomore, Junior, Senior
- **UK/General**: First Year, Second Year, Third Year, Final Year
- **Graduate**: Masters and PhD levels
- **General**: Undergraduate, Graduate, Postgraduate, Diploma, Certificate

---

### 5. Legal Areas (Specialized)

Focused list of legal specializations for law professionals.

**Endpoint:** `GET /reference/legal-areas`
**Authorization:** `Bearer {token}` (Required)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Legal areas retrieved successfully",
  "data": {
    "legal_areas": [
      "Administrative Law",
      "Banking Law",
      "Civil Law",
      "Commercial Law",
      "Constitutional Law",
      "Contract Law",
      "Corporate Law",
      "Criminal Law",
      "Employment Law",
      "Environmental Law",
      "Family Law",
      "Healthcare Law",
      "Human Rights Law",
      "Immigration Law",
      "Insurance Law",
      "Intellectual Property Law",
      "International Law",
      "Real Estate Law",
      "Tax Law",
      "Tort Law"
    ]
  }
}
```

---

### 6. Common Professions

Dynamic list of professions sourced from actual user profiles. This data grows organically as users register and includes case-insensitive consolidation for consistent display.

**Endpoint:** `GET /reference/professions`
**Authorization:** `Bearer {token}` (Required)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Common professions retrieved successfully",
  "data": {
    "professions": [
      "Doctor",
      "Engineer",
      "lawyer",
      "student"
    ]
  }
}
```

**Note:** The above shows actual sample data from the user database. Your response will contain the unique professions that users have entered in their profiles, with case variations automatically consolidated to maintain consistency.

---

## Integration Examples

### Frontend Form Population

```javascript
// Fetch all reference data for profile form
async function loadReferenceData() {
  const baseUrl = 'http://localhost:8000/api';
  const token = localStorage.getItem('auth_token');

  const headers = {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  };

  // Load areas of expertise
  const areasResponse = await fetch(`${baseUrl}/reference/areas-of-expertise`, { headers });
  const { data: { areas } } = await areasResponse.json();

  // Load countries
  const countriesResponse = await fetch(`${baseUrl}/reference/countries`, { headers });
  const { data: { countries } } = await countriesResponse.json();

  // Load academic levels
  const levelsResponse = await fetch(`${baseUrl}/reference/levels`, { headers });
  const { data: { levels } } = await levelsResponse.json();

  // Load professions
  const professionsResponse = await fetch(`${baseUrl}/reference/professions`, { headers });
  const { data: { professions } } = await professionsResponse.json();

  return {
    areas,
    countries,
    levels,
    professions
  };
}

// Build form options
function buildFormOptions(referenceData) {
  return {
    // Multi-select for areas of expertise (max 5)
    areaOptions: referenceData.areas.map(area => ({
      value: area,
      label: area
    })),

    // Single select for country
    countryOptions: referenceData.countries.map(country => ({
      value: country.country,
      label: country.country,
      code: country.country_code
    })),

    // Single select for profession
    professionOptions: referenceData.professions.map(profession => ({
      value: profession,
      label: profession.charAt(0).toUpperCase() + profession.slice(1)
    })),

    // Single select for academic level (students only)
    levelOptions: referenceData.levels.map(level => ({
      value: level.value,
      label: level.label
    }))
  };
}
```

### University Search

```javascript
// Search universities by country and name
async function searchUniversities(country = null, searchTerm = null) {
  const baseUrl = 'http://localhost:8000/api';
  const token = localStorage.getItem('auth_token');

  const params = new URLSearchParams();
  if (country) params.append('country', country);
  if (searchTerm) params.append('search', searchTerm);

  const response = await fetch(
    `${baseUrl}/reference/universities?${params.toString()}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );

  const { data: { universities } } = await response.json();

  return universities.map(uni => ({
    id: uni.id,
    name: uni.name,
    country: uni.country,
    website: uni.website
  }));
}

// Usage examples
const nigerianUnis = await searchUniversities('Nigeria');
const lagosUnis = await searchUniversities(null, 'Lagos');
const nigerianLagosUnis = await searchUniversities('Nigeria', 'Lagos');
```

### Validation Integration

```javascript
// Validate profile data against reference data
function validateProfile(profileData, referenceData) {
  const errors = {};

  // Validate profession
  if (!referenceData.professions.includes(profileData.profession)) {
    errors.profession = ['Invalid profession selected'];
  }

  // Validate areas of expertise
  if (!Array.isArray(profileData.area_of_expertise)) {
    errors.area_of_expertise = ['Areas of expertise must be an array'];
  } else {
    if (profileData.area_of_expertise.length === 0) {
      errors.area_of_expertise = ['At least one area of expertise is required'];
    } else if (profileData.area_of_expertise.length > 5) {
      errors.area_of_expertise = ['You can select up to 5 areas of expertise'];
    }

    // Check each area is valid (note: areas list is dynamic and may be limited for new systems)
    const invalidAreas = profileData.area_of_expertise.filter(
      area => !referenceData.areas.includes(area)
    );
    if (invalidAreas.length > 0) {
      errors.area_of_expertise = [`Invalid areas: ${invalidAreas.join(', ')}`];
    }
  }

  // Validate country
  const validCountries = referenceData.countries.map(c => c.country);
  if (!validCountries.includes(profileData.country)) {
    errors.country = ['Invalid country selected'];
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
}
```

---

## cURL Examples

### Get Areas of Expertise
```bash
curl -X GET "http://localhost:8000/api/reference/areas-of-expertise" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Countries
```bash
curl -X GET "http://localhost:8000/api/reference/countries" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Search Universities
```bash
# Get all universities
curl -X GET "http://localhost:8000/api/reference/universities" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Filter by country
curl -X GET "http://localhost:8000/api/reference/universities?country=Nigeria" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Search by name
curl -X GET "http://localhost:8000/api/reference/universities?search=Lagos" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Academic Levels
```bash
curl -X GET "http://localhost:8000/api/reference/levels" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Legal Areas
```bash
curl -X GET "http://localhost:8000/api/reference/legal-areas" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Professions
```bash
curl -X GET "http://localhost:8000/api/reference/professions" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Error Handling

### Authentication Errors

**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

### Invalid Parameters

**422 Unprocessable Entity:**
```json
{
  "status": "error",
  "message": "Invalid search parameters",
  "data": {
    "country": ["Country not found"],
    "search": ["Search term too short"]
  }
}
```

---

## Usage Guidelines

### Performance Optimization
- **Cache Reference Data**: Store reference data locally with shorter expiration for dynamic endpoints
- **Batch Requests**: Load all reference data on app initialization
- **Pagination**: Universities endpoint supports pagination for large datasets
- **Search Debouncing**: Implement debouncing for university search

### Data Freshness
- **Dynamic Data**: Professions and areas of expertise change as users register and update profiles
- **Semi-Static Data**: Universities, countries, levels, and legal areas change infrequently
- **Cache TTL**: Recommended 1-4 hour cache for dynamic data (professions/areas), 24-hour cache for static data
- **Real-time Growth**: Dynamic endpoints reflect current user base and grow organically

### Best Practices
- **Progressive Loading**: Load professions first, then areas based on selection
- **Validation**: Always validate user selections against current reference data
- **Fallback**: Provide "Other" options where appropriate for dynamic data
- **Accessibility**: Include proper labels and descriptions for screen readers
- **Case Handling**: Trust the API's case consolidation rather than implementing client-side normalization
- **Empty States**: Handle cases where dynamic endpoints may return limited data for new systems

---

## Notes

- **Authentication Required**: All reference endpoints require valid bearer tokens
- **Data Consistency**: Reference data is synchronized with validation rules
- **Multi-Language Support**: Currently English only, future versions may support localization
- **University Data**: University list includes global institutions with verified websites
- **Dynamic Growth**: Professions and areas grow organically with user registrations
- **Case Consolidation**: Automatic case-insensitive deduplication maintains data quality
- **System Support**: Academic levels support Nigerian, US, UK, and international systems

---

## Related Endpoints

- [Onboarding](onboarding.md) - Profile creation using reference data
- [Profile Management](profile-management.md) - Update profile with reference data
- [Authentication](authentication.md) - Required for accessing reference endpoints
- [Search](search.md) - Advanced search using reference data filters