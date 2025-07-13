  📡 API Endpoint Specification

  Endpoint:

  GET /api/admin/subscriptions/dashboard-metrics

  Complete Request/Response Structure:

  // REQUEST
  GET /api/admin/subscriptions/dashboard-metrics
  Authorization: Bearer {admin_token}

  // RESPONSE
  {
    "success": true,
    "data": {
      "financial_overview": {
        "monthly_recurring_revenue": 450000,        // Amount in kobo/cents
        "revenue_growth_rate": 12.5                 // Percentage change from last month
      },
      "subscription_counts": {
        "total": 234,                               // All subscriptions
        "active": 189,                              // Currently active subscriptions
        "attention": 12,                            // Subscriptions needing action
        "cancelled": 28,                            // Historical for calculations
        "completed": 5,                             // Historical for calculations
        "non_renewing": 15                          // Historical for calculations
      },
      "payment_health": {
        "overdue_count": 8,                         // Payments past due
        "success_rate": 94.2,                       // Payment success % this month
        "renewals_next_7_days": 23,                 // Subscriptions renewing in 7 days
        "renewals_next_30_days": 87                 // Subscriptions renewing in 30 days
      },
      "business_metrics": {
        "churn_rate": 3.2,                          // Monthly churn percentage
        "subscriber_growth_rate": 8.7               // Monthly subscriber growth %
      },
      "plan_performance": [
        {
          "plan_name": "Premium",                   // Plan display name
          "subscriber_count": 78,                   // Active subscribers on this plan
          "growth_rate": 15.2                       // Growth % for this plan
        },
        {
          "plan_name": "Basic",
          "subscriber_count": 145,
          "growth_rate": 8.1
        },
        {
          "plan_name": "Enterprise",
          "subscriber_count": 23,
          "growth_rate": 22.3
        }
      ]
    },
    "meta": {
      "last_updated": "2024-01-15T10:30:00Z",      // When data was last calculated
      "period": "last_30_days"                      // Period for growth calculations
    }
  }