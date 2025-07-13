# Subscription Management API

## Authentication

All endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Base URL
- **Local:** `http://127.0.0.1:8000/api`
- **Production:** `https://rest.lawexa.com/api`

## Subscription Management Permissions

### User Access
- **View Own Subscriptions** - Authenticated users can view their own subscriptions
- **Create Subscriptions** - Users can subscribe to plans
- **Cancel Own Subscriptions** - Users can cancel their own subscriptions
- **Reactivate Own Subscriptions** - Users can reactivate their own subscriptions
- **Manage Own Subscriptions** - Users can get management links for their subscriptions

### Admin Access
- **View All Subscriptions** - admin, researcher, or superadmin
- **View Dashboard Metrics** - admin, researcher, or superadmin
- **Cancel Any Subscription** - admin or superadmin only
- **Reactivate Any Subscription** - admin or superadmin only
- **Sync Subscriptions** - admin or superadmin only
- **View Subscription Invoices** - admin, researcher, or superadmin

### Role Restrictions
- **Researchers** - Can only view subscription details, dashboard metrics, and invoices (no modification)
- **Admins** - Can view, cancel, reactivate, and sync subscriptions, plus view dashboard metrics
- **Superadmins** - Full access to all subscription operations including dashboard metrics

---

## User Endpoints

### 1. Get User Subscriptions

**GET** `/subscriptions`

Retrieves a paginated list of the authenticated user's subscriptions with filtering capabilities.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | - | Filter by status: `active`, `attention`, `completed`, `cancelled`, `non-renewing` |
| `page` | integer | No | 1 | Page number (min: 1) |
| `per_page` | integer | No | 10 | Items per page (min: 1, max: 100) |
| `sort_by` | string | No | created_at | Sort field: `created_at`, `updated_at`, `next_payment_date`, `amount`, `status` |
| `sort_direction` | string | No | desc | Sort direction: `asc`, `desc` |

#### Example Request
```
GET /subscriptions?status=active&page=1&per_page=20&sort_by=created_at&sort_direction=desc
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscriptions retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "subscription_code": "SUB_abc123def456",
        "status": "active",
        "quantity": 1,
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "start_date": "2023-01-01T00:00:00.000000Z",
        "next_payment_date": "2023-02-01T00:00:00.000000Z",
        "cron_expression": "0 0 1 * *",
        "invoice_limit": 0,
        "is_active": true,
        "is_expired": false,
        "can_be_cancelled": true,
        "plan": {
          "id": 1,
          "name": "Premium Plan",
          "plan_code": "PLN_xyz789abc123",
          "description": "Premium subscription plan",
          "amount": 500000,
          "formatted_amount": "5000.00",
          "currency": "NGN",
          "interval": "monthly"
        },
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/subscriptions?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/subscriptions?page=3",
    "links": [],
    "next_page_url": "http://localhost:8000/api/subscriptions?page=2",
    "path": "http://localhost:8000/api/subscriptions",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 25
  }
}
```

---

### 2. Create Subscription

**POST** `/subscriptions`

Creates a new subscription for the authenticated user. Can either process immediate payment with authorization code or initialize payment flow.

#### Required Permissions
- Authenticated user

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `plan_id` | integer | Yes | exists:plans,id | ID of the plan to subscribe to |
| `authorization_code` | string | No | - | Paystack authorization code for immediate payment |
| `callback_url` | string | No | url | URL to redirect after payment completion |
| `metadata` | object | No | - | Additional metadata for the transaction |

#### Example Request (Immediate Payment)
```json
{
  "plan_id": 1,
  "authorization_code": "AUTH_abc123def456"
}
```

#### Example Request (Payment Initialization)
```json
{
  "plan_id": 1,
  "callback_url": "https://example.com/payment/callback",
  "metadata": {
    "custom_field": "value"
  }
}
```

#### Success Response - Immediate Payment (201)
```json
{
  "status": "success",
  "message": "Subscription created successfully",
  "data": {
    "subscription": {
      "id": 2,
      "subscription_code": "SUB_def456ghi789",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-02T00:00:00.000000Z",
      "next_payment_date": "2023-02-02T00:00:00.000000Z",
      "cron_expression": "0 0 2 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "created_at": "2023-01-02T00:00:00.000000Z",
      "updated_at": "2023-01-02T00:00:00.000000Z"
    }
  }
}
```

#### Success Response - Payment Initialization (201)
```json
{
  "status": "success",
  "message": "Payment initialized. Complete payment to activate subscription",
  "data": {
    "payment_url": "https://checkout.paystack.com/abc123def456",
    "access_code": "abc123def456",
    "reference": "REF_ghi789jkl012"
  }
}
```

#### Error Responses

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "plan_id": ["The plan id field is required."],
    "callback_url": ["The callback url field must be a valid URL."]
  }
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to create subscription: Paystack API Error"
}
```

---

### 3. Get Single Subscription

**GET** `/subscriptions/{subscription}`

Retrieves detailed information about a specific subscription belonging to the authenticated user.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
GET /subscriptions/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription retrieved successfully",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-01T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "invoices": [
        {
          "id": 1,
          "invoice_code": "INV_abc123def456",
          "amount": 500000,
          "formatted_amount": "5000.00",
          "currency": "NGN",
          "status": "success",
          "paid_at": "2023-01-01T00:00:00.000000Z",
          "created_at": "2023-01-01T00:00:00.000000Z"
        }
      ],
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. You can only view your own subscriptions."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Subscription not found"
}
```

---

### 4. Cancel Subscription

**POST** `/subscriptions/{subscription}/cancel`

Cancels the authenticated user's subscription.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /subscriptions/1/cancel
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription cancelled successfully",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "cancelled",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": null,
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": false,
      "is_expired": false,
      "can_be_cancelled": false,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
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
  "message": "Unauthorized. You can only cancel your own subscriptions."
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to cancel subscription: Paystack API Error"
}
```

---

### 5. Reactivate Subscription

**POST** `/subscriptions/{subscription}/reactivate`

Reactivates a previously cancelled subscription for the authenticated user.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /subscriptions/1/reactivate
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription reactivated successfully",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-02T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-02T14:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. You can only reactivate your own subscriptions."
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to reactivate subscription: Paystack API Error"
}
```

---

### 6. Get Subscription Invoices

**GET** `/subscriptions/{subscription}/invoices`

Retrieves all invoices for a specific subscription belonging to the authenticated user.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
GET /subscriptions/1/invoices
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription invoices retrieved successfully",
  "data": {
    "invoices": [
      {
        "id": 1,
        "invoice_code": "INV_abc123def456",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "status": "success",
        "paid_at": "2023-01-01T00:00:00.000000Z",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      },
      {
        "id": 2,
        "invoice_code": "INV_def456ghi789",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "status": "success",
        "paid_at": "2023-02-01T00:00:00.000000Z",
        "created_at": "2023-02-01T00:00:00.000000Z",
        "updated_at": "2023-02-01T00:00:00.000000Z"
      }
    ]
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. You can only view invoices for your own subscriptions."
}
```

---

### 7. Get Management Link

**GET** `/subscriptions/{subscription}/management-link`

Generates a Paystack management link for the subscription, allowing users to update payment methods and manage their subscription externally.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
GET /subscriptions/1/management-link
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Management link generated successfully",
  "data": {
    "management_link": "https://manage.paystack.co/subscription/SUB_abc123def456"
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to generate management link: Paystack API Error"
}
```

---

### 8. Send Management Email

**POST** `/subscriptions/{subscription}/management-email`

Sends a management email to the user with subscription management instructions.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /subscriptions/1/management-email
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Management email sent successfully",
  "data": []
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to send management email: Email service error"
}
```

---

### 9. Switch Plan

**POST** `/subscriptions/switch-plan`

Switches the user's current active subscription to a different plan.

#### Required Permissions
- Authenticated user

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `plan_id` | integer | Yes | exists:plans,id | ID of the new plan |
| `authorization_code` | string | No | - | Paystack authorization code for payment |

#### Example Request
```json
{
  "plan_id": 2,
  "authorization_code": "AUTH_abc123def456"
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Plan switched successfully",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 750000,
      "formatted_amount": "7500.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-01T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 2,
        "name": "Enterprise Plan",
        "plan_code": "PLN_enterprise123",
        "description": "Enterprise subscription plan",
        "amount": 750000,
        "formatted_amount": "7500.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-02T16:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "plan_id": ["The plan id field is required."]
  }
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to switch plan: No active subscription found"
}
```

---

### 10. Sync Subscription

**POST** `/subscriptions/{subscription}/sync`

Synchronizes the subscription data with Paystack to get the latest status and information.

#### Required Permissions
- Authenticated user (own subscriptions only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /subscriptions/1/sync
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription synced with Paystack successfully",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-01T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-02T18:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. You can only sync your own subscriptions."
}
```

**500 Server Error**
```json
{
  "status": "error",
  "message": "Failed to sync subscription: Paystack API Error"
}
```

---

## Admin Endpoints

### 1. Get Dashboard Metrics

**GET** `/admin/subscriptions/dashboard-metrics`

Retrieves comprehensive subscription analytics and business metrics with support for different time periods.

#### Required Permissions
- admin, researcher, or superadmin

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | No | monthly | Time period: `daily`, `weekly`, `monthly`, `quarterly`, `biannually`, `annually` |

#### Example Requests
```
GET /admin/subscriptions/dashboard-metrics
GET /admin/subscriptions/dashboard-metrics?period=quarterly
GET /admin/subscriptions/dashboard-metrics?period=annually
```

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "financial_overview": {
      "monthly_recurring_revenue": 450000,
      "revenue_growth_rate": 12.5,
      "new_business_revenue": 135000,
      "renewal_revenue": 315000,
      "current_mrr": 520000,
      "revenue_breakdown": {
        "new_customers": 135000,
        "renewals": 315000,
        "total": 450000
      }
    },
    "subscription_counts": {
      "total": 234,
      "active": 189,
      "attention": 12,
      "cancelled": 28,
      "completed": 5,
      "non_renewing": 15
    },
    "payment_health": {
      "overdue_count": 8,
      "success_rate": 94.2,
      "renewals_next_7_days": 23,
      "renewals_next_30_days": 87
    },
    "business_metrics": {
      "churn_rate": 3.2,
      "subscriber_growth_rate": 8.7
    },
    "plan_performance": [
      {
        "plan_name": "Premium",
        "subscriber_count": 78,
        "growth_rate": 15.2,
        "interval": "monthly"
      },
      {
        "plan_name": "Basic",
        "subscriber_count": 145,
        "growth_rate": 8.1,
        "interval": "monthly"
      },
      {
        "plan_name": "Enterprise",
        "subscriber_count": 23,
        "growth_rate": 22.3,
        "interval": "annually"
      }
    ]
  },
  "meta": {
    "last_updated": "2024-01-15T10:30:00Z",
    "period": "last_30_days"
  }
}
```

#### Period-Based Response Variations

When using different periods, the revenue key name changes:

| Period | Revenue Key | Period Label |
|--------|-------------|--------------|
| `daily` | `daily_recurring_revenue` | `last_24_hours` |
| `weekly` | `weekly_recurring_revenue` | `last_7_days` |
| `monthly` | `monthly_recurring_revenue` | `last_30_days` |
| `quarterly` | `quarterly_recurring_revenue` | `last_90_days` |
| `biannually` | `biannual_recurring_revenue` | `last_6_months` |
| `annually` | `annual_recurring_revenue` | `last_12_months` |

#### Example Quarterly Response
```json
{
  "success": true,
  "data": {
    "financial_overview": {
      "quarterly_recurring_revenue": 1350000,
      "revenue_growth_rate": 22.8,
      "new_business_revenue": 405000,
      "renewal_revenue": 945000,
      "current_mrr": 520000,
      "revenue_breakdown": {
        "new_customers": 405000,
        "renewals": 945000,
        "total": 1350000
      }
    },
    // ... other metrics
  },
  "meta": {
    "last_updated": "2024-01-15T10:30:00Z",
    "period": "last_90_days"
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins, researchers, and superadmins can view metrics."
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "period": ["The selected period is invalid."]
  }
}
```

#### Metrics Explanation

**Financial Overview:**
- `*_recurring_revenue`: **ACTUAL COLLECTED REVENUE** from paid invoices in the period (in kobo/cents) - includes both new subscriptions AND renewals
- `revenue_growth_rate`: Percentage change compared to previous period
- `new_business_revenue`: Revenue from first-time customer payments in the period
- `renewal_revenue`: Revenue from existing customer renewals in the period
- `current_mrr`: Current Monthly Recurring Revenue from all active subscriptions (normalized to monthly equivalent)
- `revenue_breakdown`: Detailed breakdown showing revenue sources

**Key Revenue Changes:**
- ✅ **Fixed**: Revenue calculations now include renewal payments (previously missed)
- ✅ **Enhanced**: Revenue breakdown by source (new vs existing customers)
- ✅ **Accurate**: Based on actual invoice payments, not just subscription creation amounts
- ✅ **Multi-interval**: Properly handles all billing periods (hourly, daily, weekly, monthly, quarterly, biannually, annually)

**Subscription Counts:**
- `total`: All subscriptions across all statuses
- `active`: Currently active and billing subscriptions
- `attention`: Subscriptions requiring action (payment issues)
- Historical counts for business calculations

**Payment Health:**
- `overdue_count`: Subscriptions with past-due payments
- `success_rate`: Payment success percentage for current period
- `renewals_next_*_days`: Upcoming renewal counts

**Business Metrics:**
- `churn_rate`: Percentage of subscriptions cancelled in period
- `subscriber_growth_rate`: Net subscriber growth percentage

**Plan Performance:**
- Per-plan active subscriber counts and growth rates
- `interval`: Plan billing period (monthly, annually, etc.)

---

## ⚠️ IMPORTANT: Enhanced Revenue Tracking

**As of [Current Date], the dashboard metrics have been significantly enhanced to provide accurate revenue tracking:**

### What Changed
1. **Revenue now includes renewals**: Previously, only new subscription amounts were counted. Now all paid invoices (new + renewals) are included.
2. **Revenue breakdown by source**: New fields show revenue from new customers vs existing customer renewals.
3. **Multiple billing interval support**: Properly handles all plan intervals (hourly to annually).
4. **Plan interval information**: Plan performance now includes the billing period for each plan.

### New Financial Overview Fields

| Field | Type | Description |
|-------|------|-------------|
| `{period}_recurring_revenue` | integer | **Total actual collected revenue** (new + renewals) |
| `new_business_revenue` | integer | Revenue from first-time customers only |
| `renewal_revenue` | integer | Revenue from existing customer renewals |
| `current_mrr` | integer | Current Monthly Recurring Revenue (all active subscriptions) |
| `revenue_breakdown` | object | Detailed breakdown: `{new_customers, renewals, total}` |

### Frontend Integration Notes

**Dashboard Cards:**
- **Primary Revenue Card**: Use `{period}_recurring_revenue` (total actual revenue)
- **Revenue Breakdown**: Use `revenue_breakdown.new_customers` and `revenue_breakdown.renewals`
- **Forward-Looking MRR**: Use `current_mrr` for projections

**Charts & Analytics:**
- Revenue trends now show **actual collected money** including renewals
- Growth rates are now accurate and include recurring business
- Plan performance includes interval information for better analysis

**Period Support:**
All calculations work correctly with `?period=daily`, `weekly`, `monthly`, `quarterly`, `biannually`, `annually`

---

### 2. Get Subscription Details (Admin)

**GET** `/admin/subscriptions/{subscription}`

Retrieves detailed information about any subscription. Admin version includes user information.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
GET /admin/subscriptions/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription details retrieved successfully",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-01T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins, researchers, and superadmins can view subscription details."
}
```

---

### 2. Cancel Subscription (Admin)

**POST** `/admin/subscriptions/{subscription}/cancel`

Allows admins to cancel any user's subscription.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /admin/subscriptions/1/cancel
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription cancelled successfully by admin",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "cancelled",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": null,
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": false,
      "is_expired": false,
      "can_be_cancelled": false,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
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
  "message": "Unauthorized. Only admins and superadmins can cancel subscriptions."
}
```

---

### 3. Reactivate Subscription (Admin)

**POST** `/admin/subscriptions/{subscription}/reactivate`

Allows admins to reactivate any user's subscription.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /admin/subscriptions/1/reactivate
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription reactivated successfully by admin",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-02T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-02T14:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can reactivate subscriptions."
}
```

---

### 4. Get Subscription Invoices (Admin)

**GET** `/admin/subscriptions/{subscription}/invoices`

Retrieves all invoices for any subscription with user information.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
GET /admin/subscriptions/1/invoices
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription invoices retrieved successfully",
  "data": {
    "invoices": [
      {
        "id": 1,
        "invoice_code": "INV_abc123def456",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "status": "success",
        "paid_at": "2023-01-01T00:00:00.000000Z",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      }
    ],
    "subscription": {
      "id": 1,
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins, researchers, and superadmins can view subscription invoices."
}
```

---

### 5. Sync Subscription (Admin)

**POST** `/admin/subscriptions/{subscription}/sync`

Allows admins to sync any subscription with Paystack.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription` | integer | Yes | Subscription ID |

#### Example Request
```
POST /admin/subscriptions/1/sync
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Subscription synced with Paystack successfully by admin",
  "data": {
    "subscription": {
      "id": 1,
      "subscription_code": "SUB_abc123def456",
      "status": "active",
      "quantity": 1,
      "amount": 500000,
      "formatted_amount": "5000.00",
      "currency": "NGN",
      "start_date": "2023-01-01T00:00:00.000000Z",
      "next_payment_date": "2023-02-01T00:00:00.000000Z",
      "cron_expression": "0 0 1 * *",
      "invoice_limit": 0,
      "is_active": true,
      "is_expired": false,
      "can_be_cancelled": true,
      "plan": {
        "id": 1,
        "name": "Premium Plan",
        "plan_code": "PLN_xyz789abc123",
        "description": "Premium subscription plan",
        "amount": 500000,
        "formatted_amount": "5000.00",
        "currency": "NGN",
        "interval": "monthly"
      },
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-02T18:00:00.000000Z"
    }
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can sync subscriptions."
}
```

---

## Data Models

### Subscription Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique subscription identifier |
| `subscription_code` | string | No | Paystack subscription code |
| `status` | string | No | Subscription status: active, attention, completed, cancelled, non-renewing |
| `quantity` | integer | No | Subscription quantity (usually 1) |
| `amount` | integer | No | Subscription amount in kobo |
| `formatted_amount` | string | No | Human-readable amount (e.g., "5000.00") |
| `currency` | string | No | Currency code (default: "NGN") |
| `start_date` | string | Yes | ISO timestamp of subscription start |
| `next_payment_date` | string | Yes | ISO timestamp of next payment |
| `cron_expression` | string | Yes | Cron expression for billing schedule |
| `invoice_limit` | integer | No | Invoice limit (0 = unlimited) |
| `is_active` | boolean | No | Whether subscription is currently active |
| `is_expired` | boolean | No | Whether subscription has expired |
| `can_be_cancelled` | boolean | No | Whether subscription can be cancelled |
| `plan` | object | No | Associated plan object (when loaded) |
| `user` | object | No | Associated user object (when loaded, admin endpoints only) |
| `invoices` | array | No | Array of invoice objects (when loaded) |
| `created_at` | string | No | ISO timestamp of subscription creation |
| `updated_at` | string | No | ISO timestamp of last update |

### Subscription Status Values

| Status | Description |
|--------|-------------|
| `active` | Subscription is active and billing normally |
| `attention` | Subscription needs attention (payment issues) |
| `completed` | Subscription has completed its billing cycle |
| `cancelled` | Subscription has been cancelled |
| `non-renewing` | Subscription will not renew after current period |

### Invoice Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique invoice identifier |
| `invoice_code` | string | No | Paystack invoice code |
| `amount` | integer | No | Invoice amount in kobo |
| `formatted_amount` | string | No | Human-readable amount |
| `currency` | string | No | Currency code |
| `status` | string | No | Invoice status (success, failed, pending) |
| `paid_at` | string | Yes | ISO timestamp of payment |
| `created_at` | string | No | ISO timestamp of invoice creation |
| `updated_at` | string | No | ISO timestamp of last update |

---

## Common Use Cases

### Get Dashboard Metrics (Default Monthly)
```
GET /admin/subscriptions/dashboard-metrics
```

### Get Quarterly Business Metrics
```
GET /admin/subscriptions/dashboard-metrics?period=quarterly
```

### Get Annual Performance Overview
```
GET /admin/subscriptions/dashboard-metrics?period=annually
```

### Subscribe to Plan
```json
POST /subscriptions
{
  "plan_id": 1,
  "callback_url": "https://example.com/callback"
}
```

### Subscribe with Existing Payment Method
```json
POST /subscriptions
{
  "plan_id": 1,
  "authorization_code": "AUTH_abc123def456"
}
```

### Get User's Active Subscriptions
```
GET /subscriptions?status=active
```

### Cancel User's Subscription
```
POST /subscriptions/1/cancel
```

### Switch to Different Plan
```json
POST /subscriptions/switch-plan
{
  "plan_id": 2,
  "authorization_code": "AUTH_abc123def456"
}
```

### Admin View Subscription Details
```
GET /admin/subscriptions/1
```

### Admin Cancel User Subscription
```
POST /admin/subscriptions/1/cancel
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created (new subscription) |
| 400 | Bad Request |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions) |
| 404 | Subscription not found |
| 422 | Validation error |
| 500 | Server error (Paystack API error, database error) |

---

## Integration Notes

### Paystack Integration
- Subscriptions are created and managed through Paystack
- `subscription_code` is generated by Paystack for subscription management
- Payment initialization returns authorization URL for completing payment
- Sync endpoints allow synchronization with Paystack for latest status

### Business Logic
- Users can only manage their own subscriptions (except admins)
- Admins can view and manage any subscription based on role permissions
- Plan switching updates the current subscription rather than creating new one
- Subscription status affects billing and access to services
- Management links provide external subscription management through Paystack