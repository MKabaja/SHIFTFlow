# Shift Flow - Frontend API Specification

> **Version:** 1.0  
> **Last Updated:** January 29, 2026  
> **Backend:** Laravel 11 + JWT Authentication  
> **Base URL:** `http://localhost:8000/api`

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Base Configuration](#base-configuration)
4. [API Endpoints](#api-endpoints)
   - [Authentication](#authentication-endpoints)
   - [Schedules](#schedules)
   - [Shifts](#shifts)
   - [Positions](#positions)
   - [Employees](#employees)
   - [Availabilities](#availabilities)
5. [Error Handling](#error-handling)
6. [Authorization & Roles](#authorization--roles)
7. [Test Accounts](#test-accounts)
8. [Tech Stack Recommendations](#tech-stack-recommendations)

---

## 📖 Overview

This document describes the REST API for the **Shift Flow** scheduling system. The API provides endpoints for managing schedules, shifts, positions, employees, and availability declarations.

### Key Features

- **JWT Authentication** (Bearer token)
- **Role-based Authorization** (Admin, Manager, Employee)
- **Batch Shift Creation** with validation
- **Schedule Publishing** workflow
- **Employee Availability** management
- **Comprehensive Error Responses**

---

## 🔐 Authentication

### Token Type
**JWT (JSON Web Token)** - Bearer authentication

### Token Lifecycle
- **Expires in:** 32400 seconds (9 hours)
- **Storage:** localStorage (frontend)
- **Header format:** `Authorization: Bearer {token}`

### Login Flows

#### 1. Admin/Manager Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "login": "admin",
  "password": "password"
}
```

**Response (200 OK):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 32400,
  "user": {
    "id": 1,
    "login": "admin",
    "name": "Admin User",
    "role": "admin"
  }
}
```

**Error (401 Unauthorized):**
```json
{
  "message": "Invalid password or login!"
}
```

---

#### 2. Employee Login (PIN-based)
```http
POST /api/auth/login-pin
```

**Request Body:**
```json
{
  "login": "mkabaj",
  "pin": 1234
}
```

**Response (200 OK):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 32400,
  "user": {
    "id": 2,
    "login": "mkabaj",
    "name": "Mateusz Kabaja",
    "role": "employee"
  }
}
```

---

#### 3. Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "login": "admin",
  "name": "Admin User",
  "role": "admin",
  "email": "admin@example.com"
}
```

---

#### 4. Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

---

## ⚙️ Base Configuration

### Axios Setup (React)

```javascript
// src/api/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor - add token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor - handle 401
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## 📚 API Endpoints

---

## 📅 Schedules

**Base Path:** `/api/schedules`  
**Authorization:** Admin, Manager

---

### 1. List Schedules

```http
GET /api/schedules
```

**Query Parameters:**
- `month` (optional) - Filter by month (1-12)
- `year` (optional) - Filter by year (2026-2031)
- `search` (optional) - Search by name (max 100 chars)
- `per_page` (optional) - Items per page (default: 20, max: 150)

**Example:**
```
GET /api/schedules?month=2&year=2026&per_page=50
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Grafik styczen",
      "description": "Grafik styczen 2026 roku",
      "month": 1,
      "year": 2026,
      "status": "draft",
      "published_at": null,
      "created_by": "Admin User",
      "total_shifts": 0,
      "created_at": "2026-01-28T21:25:22+00:00"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/schedules?page=1",
    "last": "http://localhost:8000/api/schedules?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

---

### 2. Create Schedule

```http
POST /api/schedules
```

**Request Body:**
```json
{
  "name": "Test Schedule",
  "month": 2,
  "year": 2026,
  "description": "Test description"
}
```

**Validation Rules:**
- `name`: required, string, max 255
- `month`: required, integer, 1-12
- `year`: required, integer, current_year to current_year+5
- `description`: optional, string

**Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "name": "Test Schedule",
    "description": "Test description",
    "month": 2,
    "year": 2026,
    "status": "draft",
    "published_at": null,
    "created_by": "Admin User",
    "total_shifts": 0,
    "created_at": "2026-01-28T21:27:19+00:00",
    "updated_at": "2026-01-28T21:27:19+00:00"
  },
  "message": "Schedule created successfully"
}
```

---

### 3. Get Schedule

```http
GET /api/schedules/{id}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 2,
    "name": "Test Schedule",
    "description": "Test description",
    "month": 2,
    "year": 2026,
    "status": "draft",
    "published_at": null,
    "created_by": "Admin User",
    "total_shifts": 0,
    "created_at": "2026-01-28T21:27:19+00:00"
  }
}
```

---

### 4. Update Schedule

```http
PATCH /api/schedules/{id}
```

**Request Body:**
```json
{
  "name": "Updated Name",
  "month": 2,
  "year": 2026,
  "description": "Updated description"
}
```

**Editable Fields:**
- `name`
- `description`
- `month` (only if status = draft)
- `year` (only if status = draft)

**Response (200 OK):**
```json
{
  "data": {
    "id": 2,
    "name": "Updated Name",
    "description": "Updated description",
    "month": 2,
    "year": 2026,
    "status": "draft",
    "published_at": null,
    "created_by": "Admin User",
    "total_shifts": 0,
    "created_at": "2026-01-28T21:27:19+00:00",
    "updated_at": "2026-01-28T21:30:06+00:00"
  },
  "message": "Schedule updated successfully"
}
```

---

### 5. Delete Schedule

```http
DELETE /api/schedules/{id}
```

**Response (200 OK):**
```json
{
  "message": "Schedule deleted Successfully"
}
```

**Note:** Cascade deletes all associated shifts.

---

### 6. Add Shifts (Batch)

```http
POST /api/schedules/{id}/shifts/batch
```

**Request Body:**
```json
{
  "shifts": [
    {
      "client_temp_id": "row_1",
      "user_id": 3,
      "position_id": 1,
      "date": "2026-02-03",
      "shift_start": "09:00",
      "shift_end": "17:00",
      "notes": "Optional notes"
    }
  ]
}
```

**Validation Rules (per shift):**
- `client_temp_id`: required, string (for error mapping)
- `user_id`: required, exists in users
- `position_id`: required, exists in positions
- `date`: required, date format, must match schedule month/year
- `shift_start`: required, time format (HH:MM)
- `shift_end`: required, time format (HH:MM)
- `notes`: optional, string, max 500
- `status`: optional, enum (scheduled, confirmed, cancelled)

**Business Validations:**
- No time overlaps for same user on same date
- Minimum 11h break between shifts (configurable per user)
- Max hours per month not exceeded (configurable per user)
- User assigned to position

**Response (201 Created):**
```json
{
  "message": "Batch created successfully",
  "count": 1,
  "shifts": [
    {
      "id": 1,
      "schedule_id": 3,
      "user_id": 3,
      "user_name": "janowski konrad",
      "position_id": 1,
      "position_name": "PD",
      "date": "2026-02-03",
      "shift_start": "09:00",
      "shift_end": "17:00",
      "minutes_worked": 480,
      "hours_worked": 8,
      "status": "scheduled",
      "notes": null,
      "created_at": "2026-01-28T21:37:09+00:00",
      "updated_at": "2026-01-28T21:37:09+00:00"
    }
  ]
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "Batch validation failed",
  "errors": {
    "row_1": {
      "conflict": [
        "User has another shift for this position on the selected date"
      ]
    },
    "row_2": {
      "conflict": [
        "User has another shift for this position on the selected date"
      ]
    }
  }
}
```

---

### 7. Publish Schedule

```http
POST /api/schedules/{id}/publish
```

**No request body required.**

**Response (200 OK):**
```json
{
  "id": 3,
  "name": "Batch Schedule",
  "description": "Test description",
  "month": 2,
  "year": 2026,
  "status": "published",
  "published_at": "2026-01-28T21:42:31+00:00",
  "created_by": "Admin User",
  "total_shifts": 1,
  "created_at": "2026-01-28T21:34:28+00:00",
  "updated_at": "2026-01-28T21:42:31+00:00"
}
```

**Error Response (409 Conflict):**
```json
{
  "message": "Schedule is already published"
}
```

---

## 👷 Shifts

**Base Path:** `/api/shifts`  
**Authorization:**
- **Read:** Admin, Manager, Employee (filtered)
- **Write:** Admin, Manager only

---

### 1. List Shifts

```http
GET /api/shifts
```

**Query Parameters:**
- `user_id` (optional) - Filter by user
- `date` (optional) - Filter by specific date
- `from` (optional) - Date range start
- `to` (optional) - Date range end
- `per_page` (optional) - Items per page (default: 50, max: 150)

**Employee Filtering:**
- Employees see **only their own shifts** from **published schedules**
- Managers/Admins see **all shifts**

**Example:**
```
GET /api/shifts?from=2026-02-01&to=2026-02-28
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "schedule_id": 3,
      "user_id": 3,
      "user_name": "janowski konrad",
      "position_id": 1,
      "position_name": "PD",
      "date": "2026-02-03",
      "shift_start": "09:00",
      "shift_end": "17:00",
      "minutes_worked": 480,
      "hours_worked": 8,
      "status": "scheduled",
      "notes": null,
      "created_at": "2026-01-28T21:37:09+00:00",
      "updated_at": "2026-01-28T21:37:09+00:00"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### 2. Create Shift (DEPRECATED)

```http
POST /api/shifts
```

**Response (410 Gone):**
```json
{
  "message": "Use schedule batch endpoint instead."
}
```

**Use instead:** `POST /api/schedules/{id}/shifts/batch`

---

### 3. Get Shift

```http
GET /api/shifts/{id}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "schedule_id": 3,
    "user_id": 3,
    "user_name": "janowski konrad",
    "position_id": 1,
    "position_name": "PD",
    "date": "2026-02-03",
    "shift_start": "09:00",
    "shift_end": "17:00",
    "minutes_worked": 480,
    "hours_worked": 8,
    "status": "scheduled",
    "notes": null,
    "created_at": "2026-01-28T21:37:09+00:00",
    "updated_at": "2026-01-28T21:37:09+00:00"
  }
}
```

---

### 4. Update Shift

```http
PATCH /api/shifts/{id}
```

**Request Body:**
```json
{
  "user_id": 3,
  "position_id": 1,
  "date": "2026-02-10",
  "shift_start": "09:00",
  "shift_end": "17:00",
  "notes": "Updated notes",
  "schedule_id": 3,
  "status": "scheduled"
}
```

**Editable Fields:** All fields (same validation as batch create)

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "schedule_id": 3,
    "user_id": 3,
    "user_name": "janowski konrad",
    "position_id": 1,
    "position_name": "PD",
    "date": "2026-02-10",
    "shift_start": "09:00",
    "shift_end": "17:00",
    "minutes_worked": 480,
    "hours_worked": 8,
    "status": "scheduled",
    "notes": "Updated notes",
    "created_at": "2026-01-28T21:37:09+00:00",
    "updated_at": "2026-01-28T22:12:57+00:00"
  },
  "message": "Shift updated successfully"
}
```

---

### 5. Delete Shift

```http
DELETE /api/shifts/{id}
```

**Response (200 OK):**
```json
{
  "message": "Shift deleted Successfully"
}
```

---

## 📍 Positions

**Base Path:** `/api/positions`  
**Authorization:**
- **Read:** Admin, Manager
- **Write:** Admin only

---

### 1. List Positions

```http
GET /api/positions
```

**No pagination** - returns all positions.

**Response (200 OK):**
```json
[
  {
    "id": 1,
    "name": "PD",
    "description": "Dispatcher Assistant",
    "created_by": null,
    "created_at": "2026-01-28T21:01:50.000000Z",
    "updated_at": "2026-01-28T21:01:50.000000Z",
    "creator": null
  },
  {
    "id": 2,
    "name": "B1",
    "description": "Ticketing Officer 1",
    "created_by": null,
    "created_at": "2026-01-28T21:01:50.000000Z",
    "updated_at": "2026-01-28T21:01:50.000000Z",
    "creator": null
  }
]
```

---

### 2. Get Position

```http
GET /api/positions/{id}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "PD",
  "description": "Dispatcher Assistant",
  "created_by": null,
  "created_at": "2026-01-28T21:01:50.000000Z",
  "updated_at": "2026-01-28T21:01:50.000000Z",
  "creator": null,
  "shifts": []
}
```

---

### 3. Create Position

```http
POST /api/positions
```

**Request Body:**
```json
{
  "name": "B9",
  "description": "This is a test position"
}
```

**Validation Rules:**
- `name`: required, string, unique, max 4 chars
- `description`: optional, string, max 255

**Response (201 Created):**
```json
{
  "name": "B9",
  "description": "This is a test position",
  "created_by": 1,
  "updated_at": "2026-01-28T22:30:47.000000Z",
  "created_at": "2026-01-28T22:30:47.000000Z",
  "id": 27
}
```

---

### 4. Update Position

```http
PATCH /api/positions/{id}
```

**Request Body:**
```json
{
  "name": "B9U",
  "description": "Updated description"
}
```

**Response (200 OK):**
```json
{
  "message": "Position updated Successfully",
  "position": {
    "id": 27,
    "name": "B9U",
    "description": "Updated description",
    "created_by": 1,
    "created_at": "2026-01-28T22:30:47.000000Z",
    "updated_at": "2026-01-28T22:34:30.000000Z"
  }
}
```

---

### 5. Delete Position

```http
DELETE /api/positions/{id}
```

**Response (200 OK):**
```json
{
  "message": "Position deleted Successfully"
}
```

**Error Response (409 Conflict):**
```json
{
  "error": "INTEGRITY_VIOLATION: Cannot delete position, it is currently linked to one or more schedules."
}
```

---

## 👥 Employees

**Base Path:** `/api/employees`  
**Authorization:** Admin only

---

### 1. List Employees

```http
GET /api/employees
```

**Query Parameters:**
- `search` (optional) - Search by name
- `per_page` (optional) - Items per page (default: 50, max: 150)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 2,
      "name": "Mateusz Kabaja",
      "email": "mkabaj@example.com",
      "login": "mkabaj",
      "role": "employee",
      "is_active": true,
      "hourly_rate": null,
      "monthly_hour_limit": 0,
      "quarter_hour_limit": 0,
      "break_limit": 0,
      "contract_type": "employment_contract",
      "positions": [],
      "created_at": "2026-01-28T21:01:50+00:00"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### 2. Get Employee

```http
GET /api/employees/{id}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 5,
    "name": "korczak kamil",
    "email": null,
    "login": "kkamil",
    "role": "employee",
    "is_active": true,
    "hourly_rate": null,
    "monthly_hour_limit": 0,
    "quarter_hour_limit": 0,
    "break_limit": 0,
    "contract_type": "employment_contract",
    "positions": [
      {
        "id": 2,
        "name": "B1",
        "description": "Ticketing Officer 1"
      }
    ]
  }
}
```

---

### 3. Create Employee

```http
POST /api/employees
```

**Request Body:**
```json
{
  "name": "Test Employee",
  "pin": 9999,
  "positions": [1, 2],
  "hourly_rate": 28.50,
  "contract_type": "employment_contract",
  "max_minutes_per_month": 10080,
  "max_minutes_per_quarter": 30240,
  "min_break_minutes": 660
}
```

**Validation Rules:**
- `name`: required, string, max 255
- `pin`: required, 4 digits, numeric
- `positions`: required, array, min 1, each exists in positions table
- `hourly_rate`: optional, numeric, min 0
- `contract_type`: optional, enum (employment_contract, mandate_contract)
- `max_minutes_per_month`: optional, integer, min 0
- `max_minutes_per_quarter`: optional, integer, min 0
- `min_break_minutes`: optional, integer, min 0

**Response (201 Created):**
```json
{
  "data": {
    "id": 72,
    "name": "Test Employee",
    "email": null,
    "login": "templo",
    "role": "employee",
    "is_active": false,
    "hourly_rate": 28.5,
    "monthly_hour_limit": 168,
    "quarter_hour_limit": 504,
    "break_limit": 11,
    "contract_type": "employment_contract",
    "positions": [
      {
        "id": 2,
        "name": "B1",
        "description": "Ticketing Officer 1"
      }
    ],
    "created_at": "2026-01-28T22:53:21+00:00"
  },
  "message": "Employee created successfully"
}
```

---

### 4. Update Employee

```http
PATCH /api/employees/{id}
```

**Request Body:**
```json
{
  "name": "updated Test",
  "hourly_rate": 20.5,
  "positions": [5, 7]
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 72,
    "name": "updated Test",
    "email": null,
    "login": "templo",
    "role": "employee",
    "is_active": true,
    "hourly_rate": 20.5,
    "monthly_hour_limit": 168,
    "quarter_hour_limit": 504,
    "break_limit": 11,
    "contract_type": "employment_contract",
    "positions": [
      {
        "id": 5,
        "name": "B4",
        "description": "Ticketing Officer 4"
      }
    ],
    "created_at": "2026-01-28T22:53:21+00:00"
  },
  "message": "Employee updated successfully"
}
```

---

### 5. Delete Employee

```http
DELETE /api/employees/{id}
```

**Response (200 OK):**
```json
{
  "message": "Employee deleted successfully"
}
```

---

## 📆 Availabilities

**Base Path:** `/api/availabilities`  
**Authorization:** All authenticated users

---

### 1. List Availabilities

```http
GET /api/availabilities
```

**Query Parameters:**
- `user_id` (optional, admin/manager only) - Filter by user

**Employee Filtering:**
- Employees see **only their own** availabilities
- Managers/Admins see **all** (or filtered by user_id)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "notes": "moge tylko wtedy",
      "is_available": true,
      "date": "2025-01-29",
      "created_at": "2026-01-28T23:02:03+00:00",
      "updated_at": "2026-01-28T23:02:03+00:00"
    }
  ]
}
```

---

### 2. Create/Update Availability (Upsert)

```http
POST /api/availabilities
```

**Request Body:**
```json
{
  "user_id": "2",
  "date": "2026-02-20",
  "is_available": true,
  "notes": "moge tylko wtedy"
}
```

**Validation Rules:**
- `user_id`: optional (auto-assigned for employees)
- `date`: required, date format, unique per user
- `is_available`: required, boolean
- `notes`: optional, string, max 255

**Behavior:**
- If availability exists for user+date → **UPDATE** (200)
- If not exists → **CREATE** (201)

**Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "user_id": "2",
    "notes": "moge tylko wtedy",
    "is_available": true,
    "date": "2026-02-20",
    "created_at": "2026-01-28T23:02:38+00:00",
    "updated_at": "2026-01-28T23:02:38+00:00"
  },
  "message": "Availability created successfully"
}
```

**Response (200 OK - Updated):**
```json
{
  "data": {
    "id": 5,
    "user_id": 3,
    "notes": "moge tylko wtedy",
    "is_available": false,
    "date": "2026-02-22",
    "created_at": "2026-01-28T23:27:24+00:00",
    "updated_at": "2026-01-28T23:27:35+00:00"
  },
  "message": "Availability updated successfully"
}
```

---

### 3. Delete Availability

```http
DELETE /api/availabilities/{id}
```

**Authorization:** Owner (employee) or Admin/Manager

**Response (200 OK):**
```json
{
  "message": "Availability deleted successfully"
}
```

---

## ⚠️ Error Handling

### Standard Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 403 Forbidden
```json
{
  "message": "Unauthorized"
}
```

#### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Schedule] 999"
}
```

#### 422 Validation Error
```json
{
  "message": "The given data was invalid",
  "errors": {
    "month": ["The month field is required"],
    "year": ["The year must be between 2026 and 2031"]
  }
}
```

#### 409 Conflict
```json
{
  "message": "Schedule is already published"
}
```

#### 410 Gone (Deprecated)
```json
{
  "message": "Use schedule batch endpoint instead."
}
```

---

## 🔒 Authorization & Roles

### Role Hierarchy

| Role | Level | Permissions |
|------|-------|-------------|
| **Admin** | 3 | Full access to all endpoints |
| **Manager** | 2 | Schedules, Shifts, Positions (read), Employees (read), Reports |
| **Employee** | 1 | Own shifts (published only), Own availabilities |

### Endpoint Permissions Matrix

| Endpoint | Admin | Manager | Employee |
|----------|-------|---------|----------|
| **Authentication** | ✅ | ✅ | ✅ |
| **Schedules (CRUD)** | ✅ | ✅ | ❌ |
| **Schedules (Publish)** | ✅ | ✅ | ❌ |
| **Shifts (Write)** | ✅ | ✅ | ❌ |
| **Shifts (Read)** | ✅ (all) | ✅ (all) | ✅ (own, published) |
| **Positions (Read)** | ✅ | ✅ | ❌ |
| **Positions (Write)** | ✅ | ❌ | ❌ |
| **Employees (CRUD)** | ✅ | ❌ | ❌ |
| **Availabilities (Own)** | ✅ | ✅ | ✅ |
| **Availabilities (All)** | ✅ | ✅ | ❌ |

---

## 🧪 Test Accounts

### Admin Account
```
Login: admin
Password: password
Role: admin
User ID: 1
```

### Manager Account
```
Login: manager1
Password: password
Role: manager
User ID: (varies)
```

### Employee Account
```
Login: mkabaj
PIN: 1234
Role: employee
User ID: 2
```

---

## 🛠️ Tech Stack Recommendations

### Frontend (React)

```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.20.0",
    "axios": "^1.6.0",
    "@tanstack/react-query": "^5.0.0",
    "date-fns": "^3.0.0"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.2.0",
    "vite": "^5.0.0"
  }
}
```

### Recommended Libraries

- **HTTP Client:** Axios (with interceptors)
- **State Management:** React Query (server state) + Context API (auth)
- **Routing:** React Router v6
- **Date Handling:** date-fns or Day.js
- **Forms:** React Hook Form + Zod validation
- **UI Components:** Headless UI or Radix UI
- **Styling:** Tailwind CSS

### Project Structure

```
frontend/
├── src/
│   ├── api/
│   │   ├── axios.js          // Axios instance
│   │   ├── auth.js           // Auth endpoints
│   │   ├── schedules.js      // Schedule endpoints
│   │   ├── shifts.js         // Shift endpoints
│   │   └── ...
│   ├── components/
│   │   ├── Layout/
│   │   ├── Auth/
│   │   ├── Schedule/
│   │   └── Shift/
│   ├── pages/
│   │   ├── Login.jsx
│   │   ├── Dashboard.jsx
│   │   ├── ScheduleList.jsx
│   │   └── ShiftList.jsx
│   ├── hooks/
│   │   ├── useAuth.js
│   │   └── useShifts.js
│   ├── context/
│   │   └── AuthContext.jsx
│   └── App.jsx
├── public/
└── package.json
```

---

## 📌 Notes

### Important Behaviors

1. **Employee Shift Visibility:**
   - Employees see ONLY shifts from **published** schedules
   - Managers/Admins see all shifts regardless of status

2. **Batch Shift Creation:**
   - Validates business rules (time overlap, break times, max hours)
   - Returns mapped errors by `client_temp_id`
   - All-or-nothing transaction (DB rollback on error)

3. **Schedule Publishing:**
   - One-way operation (no unpublish in current version)
   - Once published, status changes to `published`
   - Sets `published_at` timestamp

4. **Availability Upsert:**
   - Same user + same date = UPDATE existing
   - Different date = CREATE new
   - No duplicate availabilities per user per date

5. **Cascade Deletes:**
   - Deleting Schedule → deletes all Shifts
   - Deleting Position with Shifts → 409 error (protected)

---

## 🚀 Getting Started (Frontend Developer)

### 1. Setup Project

```bash
npm create vite@latest shift-flow-frontend -- --template react
cd shift-flow-frontend
npm install axios react-router-dom @tanstack/react-query
```

### 2. Configure Axios

Copy the [Axios Setup](#base-configuration) code to `src/api/axios.js`

### 3. Implement Auth Flow

```javascript
// src/api/auth.js
import api from './axios';

export const login = async (login, password) => {
  const { data } = await api.post('/auth/login', { login, password });
  localStorage.setItem('token', data.access_token);
  return data;
};

export const logout = async () => {
  await api.post('/auth/logout');
  localStorage.removeItem('token');
};

export const getCurrentUser = async () => {
  const { data } = await api.get('/auth/me');
  return data;
};
```

### 4. Test Authentication

```javascript
// Test in browser console
import { login } from './api/auth';

const result = await login('admin', 'password');
console.log(result); // Should have access_token
```

### 5. Implement Protected Routes

```javascript
// src/components/ProtectedRoute.jsx
import { Navigate } from 'react-router-dom';

export default function ProtectedRoute({ children, allowedRoles }) {
  const token = localStorage.getItem('token');
  const user = JSON.parse(localStorage.getItem('user') || '{}');

  if (!token) return <Navigate to="/login" />;
  if (allowedRoles && !allowedRoles.includes(user.role)) {
    return <Navigate to="/unauthorized" />;
  }

  return children;
}
```

---

## 📞 Support

For questions about this API specification, contact the backend team or refer to the documentation.

---

**Last Updated:** January 29, 2026  
**Document Version:** 1.0  
**API Version:** Laravel 11 (Stable)
