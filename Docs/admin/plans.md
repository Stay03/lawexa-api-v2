# Plan Management API

## Authentication

All endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Base URL
- **Local:** `http://127.0.0.1:8000/api`
- **Production:** `https://rest.lawexa.com/api`

## Plan Management Permissions

### Public Access
- **View Plans** - Anyone can view active plans (no authentication required)

### Admin Access Required
- **Create Plans** - admin or superadmin only
- **Update Plans** - admin or superadmin only  
- **Delete/Deactivate Plans** - admin or superadmin only
- **Activate Plans** - admin or superadmin only
- **Sync with Paystack** - admin or superadmin only

### Role Restrictions
- **Researchers** - Can only view plans (same as public access)
- **Regular Users** - Can only view plans (same as public access)

---

## Endpoints

### 1. Get Plans List

**GET** `/plans`

Retrieves a list of all active subscription plans available to users. This is a public endpoint.

#### Required Permissions
- None (public endpoint)

#### Example Request
```
GET /plans
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plans retrieved successfully",
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Basic Plan",
        "plan_code": "PLN_abc123def456",
        "description": "Basic subscription plan",
        "amount": 100000,
        "formatted_amount": "1000.00",
        "currency": "NGN",
        "interval": "monthly",
        "invoice_limit": 0,
        "send_invoices": true,
        "send_sms": true,
        "hosted_page": null,
        "is_active": true,
        "subscriptions_count": 15,
        "active_subscriptions_count": 12,
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      }
    ]
  }
}
```

---

### 2. Get Single Plan

**GET** `/plans/{plan}`

Retrieves detailed information about a specific plan. This is a public endpoint.

#### Required Permissions
- None (public endpoint)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan` | integer | Yes | Plan ID |

#### Example Request
```
GET /plans/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plan retrieved successfully",
  "data": {
    "plan": {
      "id": 1,
      "name": "Basic Plan",
      "plan_code": "PLN_abc123def456",
      "description": "Basic subscription plan with full access",
      "amount": 100000,
      "formatted_amount": "1000.00",
      "currency": "NGN",
      "interval": "monthly",
      "invoice_limit": 0,
      "send_invoices": true,
      "send_sms": true,
      "hosted_page": null,
      "is_active": true,
      "subscriptions_count": 15,
      "active_subscriptions_count": 12,
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**404 Not Found**
```json
{
  "status": "error",
  "message": "Plan not found"
}
```

---

### 3. Create Plan

**POST** `/admin/plans`

Creates a new subscription plan and syncs it with Paystack.

#### Required Permissions
- admin or superadmin

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | Yes | max:255 | Plan display name |
| `description` | string | No | - | Plan description |
| `amount` | integer | Yes | min:100 | Plan amount in kobo (₦1 = 100 kobo) |
| `interval` | string | Yes | in:hourly,daily,weekly,monthly,quarterly,biannually,annually | Billing interval |
| `invoice_limit` | integer | No | min:0 | Number of invoices before stopping (0 = unlimited) |
| `send_invoices` | boolean | No | - | Whether to send email invoices |
| `send_sms` | boolean | No | - | Whether to send SMS notifications |

#### Billing Intervals

| Interval | Description |
|----------|-------------|
| `hourly` | Billed every hour |
| `daily` | Billed every day |
| `weekly` | Billed every week |
| `monthly` | Billed every month |
| `quarterly` | Billed every 3 months |
| `biannually` | Billed every 6 months |
| `annually` | Billed every year |

#### Example Request
```json
{
  "name": "Premium Plan",
  "description": "Premium subscription with advanced features",
  "amount": 500000,
  "interval": "monthly",
  "invoice_limit": 12,
  "send_invoices": true,
  "send_sms": false
}
```

#### Success Response (201)
```json
{
  "status": "success",
  "message": "Plan created successfully",
  "data": {
    "plan": {
      "id": 2,
      "name": "Premium Plan",
      "plan_code": "PLN_xyz789abc123",
      "description": "Premium subscription with advanced features",
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "interval": "monthly",
      "invoice_limit": 12,
      "send_invoices": true,
      "send_sms": false,
      "hosted_page": null,
      "is_active": true,
      "subscriptions_count": 0,
      "active_subscriptions_count": 0,
      "created_at": "2023-01-02T00:00:00.000000Z",
      "updated_at": "2023-01-02T00:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can create plans."
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "amount": ["The amount field must be at least 100."],
    "interval": ["The selected interval is invalid."]
  }
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to create plan: Paystack API Error"
}
```

---

### 4. Update Plan

**PUT** `/admin/plans/{plan}`

Updates an existing subscription plan. Can optionally update existing subscriptions.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan` | integer | Yes | Plan ID |

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | No | max:255 | Plan display name |
| `description` | string | No | - | Plan description |
| `amount` | integer | No | min:100 | Plan amount in kobo |
| `interval` | string | No | in:hourly,daily,weekly,monthly,quarterly,biannually,annually | Billing interval |
| `invoice_limit` | integer | No | min:0 | Invoice limit (0 = unlimited) |
| `send_invoices` | boolean | No | - | Send email invoices |
| `send_sms` | boolean | No | - | Send SMS notifications |
| `update_existing_subscriptions` | boolean | No | - | Whether to update existing subscriptions |

#### Example Request
```json
{
  "name": "Premium Plan Updated",
  "amount": 600000,
  "description": "Updated premium plan with more features",
  "update_existing_subscriptions": true
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plan updated successfully",
  "data": {
    "plan": {
      "id": 2,
      "name": "Premium Plan Updated",
      "plan_code": "PLN_xyz789abc123",
      "description": "Updated premium plan with more features",
      "amount": 600000,
      "formatted_amount": "6000.00",
      "currency": "NGN",
      "interval": "monthly",
      "invoice_limit": 12,
      "send_invoices": true,
      "send_sms": false,
      "hosted_page": null,
      "is_active": true,
      "subscriptions_count": 5,
      "active_subscriptions_count": 3,
      "created_at": "2023-01-02T00:00:00.000000Z",
      "updated_at": "2023-01-02T12:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can update plans."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Plan not found"
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount field must be at least 100."],
    "interval": ["The selected interval is invalid."]
  }
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to update plan: Paystack API Error"
}
```

---

### 5. Deactivate Plan

**DELETE** `/admin/plans/{plan}`

Deactivates a subscription plan. Plans with active subscriptions cannot be deleted.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan` | integer | Yes | Plan ID |

#### Business Rules
- Plans with active subscriptions cannot be deactivated
- Deactivated plans are hidden from public plan listings
- Existing inactive subscriptions remain unchanged

#### Example Request
```
DELETE /admin/plans/2
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plan deactivated successfully",
  "data": []
}
```

#### Error Responses

**400 Bad Request - Active Subscriptions**
```json
{
  "status": "error",
  "message": "Cannot delete plan with active subscriptions"
}
```

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can delete plans."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Plan not found"
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to deactivate plan: Database error"
}
```

---

## Additional Admin Endpoints

### 6. Activate Plan

**POST** `/admin/plans/{plan}/activate`

Reactivates a previously deactivated plan.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan` | integer | Yes | Plan ID |

#### Example Request
```
POST /admin/plans/2/activate
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plan activated successfully",
  "data": {
    "plan": {
      "id": 2,
      "name": "Premium Plan",
      "plan_code": "PLN_xyz789abc123",
      "description": "Premium subscription plan",
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "interval": "monthly",
      "invoice_limit": 12,
      "send_invoices": true,
      "send_sms": false,
      "hosted_page": null,
      "is_active": true,
      "subscriptions_count": 5,
      "active_subscriptions_count": 3,
      "created_at": "2023-01-02T00:00:00.000000Z",
      "updated_at": "2023-01-02T12:30:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can activate plans."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Plan not found"
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to activate plan: Database error"
}
```

---

### 7. Sync with Paystack

**POST** `/admin/plans/{plan}/sync`

Synchronizes plan data with Paystack payment processor.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan` | integer | Yes | Plan ID |

#### Example Request
```
POST /admin/plans/2/sync
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plan synced with Paystack successfully",
  "data": {
    "plan": {
      "id": 2,
      "name": "Premium Plan",
      "plan_code": "PLN_xyz789abc123",
      "description": "Premium subscription plan",
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "interval": "monthly",
      "invoice_limit": 12,
      "send_invoices": true,
      "send_sms": false,
      "hosted_page": null,
      "is_active": true,
      "subscriptions_count": 5,
      "active_subscriptions_count": 3,
      "created_at": "2023-01-02T00:00:00.000000Z",
      "updated_at": "2023-01-02T12:45:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can sync plans."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Plan not found"
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to sync plan: Paystack API error"
}
```

---

## Data Models

### Plan Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique plan identifier |
| `name` | string | No | Plan display name |
| `plan_code` | string | No | Paystack plan code (auto-generated) |
| `description` | string | Yes | Plan description |
| `amount` | integer | No | Plan amount in kobo (smallest currency unit) |
| `formatted_amount` | string | No | Human-readable amount (e.g., "1000.00") |
| `currency` | string | No | Currency code (default: "NGN") |
| `interval` | string | No | Billing interval |
| `invoice_limit` | integer | No | Invoice limit (0 = unlimited) |
| `send_invoices` | boolean | No | Whether to send email invoices |
| `send_sms` | boolean | No | Whether to send SMS notifications |
| `hosted_page` | string | Yes | Paystack hosted payment page URL |
| `is_active` | boolean | No | Whether plan is active and available |
| `subscriptions_count` | integer | No | Total number of subscriptions (when loaded) |
| `active_subscriptions_count` | integer | No | Number of active subscriptions (when loaded) |
| `created_at` | string | No | ISO timestamp of plan creation |
| `updated_at` | string | No | ISO timestamp of last update |

---

## Currency and Amounts

### Amount Format
- All amounts are stored in **kobo** (smallest currency unit)
- ₦1.00 = 100 kobo
- Minimum amount: 100 kobo (₦1.00)
- Example: ₦50.00 = 5000 kobo

### Supported Currency
- **NGN** (Nigerian Naira) - Default and primary currency

---

## Common Use Cases

### Get All Active Plans
```
GET /plans
```

### Get Specific Plan Details
```
GET /plans/1
```

### Create Monthly Plan
```json
POST /admin/plans
{
  "name": "Monthly Premium",
  "description": "Premium monthly subscription",
  "amount": 500000,
  "interval": "monthly",
  "send_invoices": true,
  "send_sms": false
}
```

### Create Annual Plan with Limit
```json
POST /admin/plans
{
  "name": "Annual Plan",
  "description": "Annual subscription with 12 invoice limit",
  "amount": 5000000,
  "interval": "annually",
  "invoice_limit": 12,
  "send_invoices": true,
  "send_sms": true
}
```

### Update Plan Pricing
```json
PUT /admin/plans/1
{
  "amount": 600000,
  "update_existing_subscriptions": true
}
```

### Deactivate Plan
```
DELETE /admin/plans/1
```

### Reactivate Plan
```
POST /admin/plans/1/activate
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created (new plan) |
| 400 | Bad Request (e.g., plan has active subscriptions) |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions) |
| 404 | Plan not found |
| 422 | Validation error |
| 500 | Server error (Paystack API error, database error) |

---

## Integration Notes

### Paystack Integration
- Plans are automatically synced with Paystack when created/updated
- `plan_code` is generated by Paystack and used for payment processing
- Plan changes can optionally update existing subscriptions
- Sync endpoint allows manual synchronization with Paystack

### Business Logic
- Plans with active subscriptions cannot be deleted (only deactivated)
- Deactivated plans are hidden from public listings but existing subscriptions continue
- Amount updates can cascade to existing subscriptions if specified
- All monetary amounts are handled in the smallest currency unit (kobo)