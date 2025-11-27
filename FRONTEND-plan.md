# Frontend MVP - Plan Implementacji (TDD-Style z Dokumentacją)

## Aplikacja do Zarządzania Grafikami - Kopalnia Soli Wieliczka

---

## 1. EXECUTIVE SUMMARY

Budujemy React SPA (Single Page Application) dla systemu obsługi grafików turystycznych w kopalni Soli Wieliczka. Frontend obsługuje:
- 3 role: pracownik, kierownik, admin
- Login: email/hasło (kierownik/admin) lub ID/PIN (pracownik)
- Widok kalendarza z możliwością drag & drop (zmiana godzin)
- Zarządzanie dyspozycjami (kalendarz dostępności)
- Raporty godzin i finansowe
- Responsywny interfejs (mobile-first)

**Zakres MVP:** Setup, auth, komponenty, routing, API integration, testy, dokumentacja.

---

## 2. STACK TECHNICZNY

- **Framework:** React 18+ (Vite)
- **Routing:** React Router v6
- **State Management:** Zustand (lightweight) / Redux (optional)
- **HTTP Client:** Axios
- **UI Components:** Tailwind CSS + shadcn/ui (accessible components)
- **Forms:** React Hook Form + Zod (validation)
- **Calendar:** React Big Calendar (schedule visualization)
- **Date/Time:** Day.js (lightweight alternative to Moment.js)
- **Testing:** Vitest + React Testing Library
- **Build:** Vite
- **Package Manager:** npm / pnpm

---

## 3. ARCHITEKTURA

```
React SPA (Vite)
    ↓
Router (React Router v6)
    ↓
Pages/Screens
    ├── Auth (Login/PIN)
    ├── Dashboard (Role-based)
    ├── Schedule Calendar (drag & drop)
    ├── Availability Manager
    ├── Reports
    └── Employee Management (admin)
    ↓
Components (reusable UI)
    ├── Forms, Buttons, Modals
    ├── Calendar, Table, Charts
    └── Navigation
    ↓
Services / Hooks
    ├── API Client (Axios)
    ├── Auth Context/Store
    ├── useSchedule, useAvailability
    └── Custom hooks
    ↓
REST API (Laravel Backend)
```

---

## 4. STRUKTURA PROJEKTU REACT

```
react-schedule-app/
├── public/
│   └── index.html
├── src/
│   ├── assets/
│   │   ├── icons/
│   │   ├── images/
│   │   └── styles/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button.jsx
│   │   │   ├── Modal.jsx
│   │   │   ├── Card.jsx
│   │   │   ├── Input.jsx
│   │   │   ├── FormField.jsx
│   │   │   └── Navbar.jsx
│   │   ├── auth/
│   │   │   ├── LoginForm.jsx
│   │   │   ├── PinLoginForm.jsx
│   │   │   └── ProtectedRoute.jsx
│   │   ├── schedule/
│   │   │   ├── ScheduleCalendar.jsx
│   │   │   ├── ScheduleForm.jsx
│   │   │   └── ScheduleCard.jsx
│   │   ├── availability/
│   │   │   ├── AvailabilityCalendar.jsx
│   │   │   ├── AvailabilityForm.jsx
│   │   │   └── AvailabilityList.jsx
│   │   ├── reports/
│   │   │   ├── HoursReport.jsx
│   │   │   ├── PayrollReport.jsx
│   │   │   └── CoverageReport.jsx
│   │   └── admin/
│   │       ├── EmployeeList.jsx
│   │       ├── EmployeeForm.jsx
│   │       ├── EmployeeImport.jsx
│   │       └── EmployeeModal.jsx
│   ├── pages/
│   │   ├── Dashboard.jsx
│   │   ├── Login.jsx
│   │   ├── Schedule.jsx
│   │   ├── Availability.jsx
│   │   ├── Reports.jsx
│   │   ├── Admin.jsx
│   │   └── NotFound.jsx
│   ├── hooks/
│   │   ├── useAuth.js
│   │   ├── useSchedule.js
│   │   ├── useAvailability.js
│   │   ├── useFetch.js
│   │   └── useForm.js
│   ├── services/
│   │   ├── api.js (Axios instance + interceptors)
│   │   ├── authService.js
│   │   ├── scheduleService.js
│   │   ├── availabilityService.js
│   │   ├── reportService.js
│   │   └── employeeService.js
│   ├── store/
│   │   ├── authStore.js (Zustand)
│   │   ├── scheduleStore.js
│   │   └── availabilityStore.js
│   ├── context/
│   │   └── AuthContext.jsx (or use Store instead)
│   ├── utils/
│   │   ├── dateUtils.js
│   │   ├── formatters.js
│   │   ├── validators.js
│   │   └── constants.js
│   ├── App.jsx
│   ├── App.css
│   ├── main.jsx
│   └── index.css (Tailwind imports)
├── tests/
│   ├── components/
│   │   ├── Button.test.jsx
│   │   ├── LoginForm.test.jsx
│   │   └── ScheduleCalendar.test.jsx
│   ├── hooks/
│   │   ├── useAuth.test.js
│   │   └── useSchedule.test.js
│   ├── services/
│   │   ├── authService.test.js
│   │   └── scheduleService.test.js
│   └── setup.js
├── .env.example
├── vite.config.js
├── package.json
├── tailwind.config.js
├── postcss.config.js
└── README.md
```

---

## 5. FLOW AUTENTYKACJI

### Login Director/Admin (email + password)
```
POST /api/auth/login
  ↓
Store JWT token (localStorage)
  ↓
Set Authorization header: Bearer {token}
  ↓
Fetch user data (GET /api/auth/me)
  ↓
Store in Zustand: user, role, token
  ↓
Navigate to Dashboard
```

### Login Employee (ID + PIN)
```
POST /api/auth/login-pin
  ↓
Store JWT token (localStorage)
  ↓
Set Authorization header: Bearer {token}
  ↓
Fetch user data (GET /api/auth/me)
  ↓
Store in Zustand: user, role, token
  ↓
Navigate to Employee Dashboard
```

### Logout
```
Remove token from localStorage
  ↓
Clear Zustand store
  ↓
Navigate to Login
```

---

## 6. KOMPONENTY KLUCZOWE

### Strony (Pages)
- **Login.jsx** — login form (email/password + PIN toggle)
- **Dashboard.jsx** — role-based dashboard (employee/manager/admin)
- **Schedule.jsx** — calendar view + drag & drop
- **Availability.jsx** — availability calendar (pracownik)
- **Reports.jsx** — hours, payroll, coverage reports
- **Admin.jsx** — employee management, CSV import
- **NotFound.jsx** — 404 page

### Komponenty (Components)
- **ProtectedRoute.jsx** — check JWT token + role
- **Navbar.jsx** — navigation + logout
- **LoginForm.jsx** — email/password form
- **PinLoginForm.jsx** — ID/PIN form
- **ScheduleCalendar.jsx** — React Big Calendar + drag & drop
- **AvailabilityCalendar.jsx** — date picker dla dyspozycji
- **HoursReport.jsx** — tabela z godzinami per miesiąc
- **PayrollReport.jsx** — tabela z wynagrodzeniami
- **EmployeeList.jsx** — admin: lista pracowników
- **EmployeeImport.jsx** — CSV import form

---

## 7. STATE MANAGEMENT (Zustand)

### authStore.js
```javascript
create((set) => ({
  user: null,
  token: localStorage.getItem('token') || null,
  role: null,
  isAuthenticated: !!localStorage.getItem('token'),
  
  setAuth: (user, token) => set({ user, token, role: user.role, isAuthenticated: true }),
  logout: () => set({ user: null, token: null, role: null, isAuthenticated: false }),
  setUser: (user) => set({ user }),
}))
```

### scheduleStore.js
```javascript
create((set) => ({
  schedules: [],
  loading: false,
  
  setSchedules: (schedules) => set({ schedules }),
  setLoading: (loading) => set({ loading }),
  addSchedule: (schedule) => set((state) => ({ schedules: [...state.schedules, schedule] })),
  updateSchedule: (id, updated) => set((state) => ({
    schedules: state.schedules.map(s => s.id === id ? updated : s)
  })),
  deleteSchedule: (id) => set((state) => ({
    schedules: state.schedules.filter(s => s.id !== id)
  })),
}))
```

### availabilityStore.js
```javascript
create((set) => ({
  availabilities: [],
  loading: false,
  
  setAvailabilities: (availabilities) => set({ availabilities }),
  setLoading: (loading) => set({ loading }),
  addAvailability: (availability) => set((state) => ({ availabilities: [...state.availabilities, availability] })),
  deleteAvailability: (id) => set((state) => ({
    availabilities: state.availabilities.filter(a => a.id !== id)
  })),
}))
```

---

## 8. API INTEGRATION (Axios + Services)

### api.js
```javascript
import axios from 'axios';

const API = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
});

// Interceptor: dodaj JWT token do każdego request
API.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor: handle 401 (token expired)
API.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default API;
```

### authService.js
```javascript
import API from './api';

export const authService = {
  login: (email, password) => API.post('/auth/login', { email, password }),
  loginPin: (employee_id, pin) => API.post('/auth/login-pin', { employee_id, pin }),
  getCurrentUser: () => API.get('/auth/me'),
};
```

### scheduleService.js
```javascript
import API from './api';

export const scheduleService = {
  getSchedules: (params) => API.get('/schedules', { params }),
  createSchedule: (data) => API.post('/schedules', data),
  updateSchedule: (id, data) => API.put(`/schedules/${id}`, data),
  deleteSchedule: (id) => API.delete(`/schedules/${id}`),
};
```

---

## 9. SPECYFIKACJE KOMPONENTÓW (ZADANIA TDD)

### SESJA 1-2: Setup & Project Structure

#### Zadanie 1.1: Inicjalizacja projektu Vite + React
**Dokumentacja:**
- https://vitejs.dev/guide/
- https://react.dev/
- https://create-vite.com/

- [ ] `npm create vite@latest react-schedule-app -- --template react`
- [ ] `cd react-schedule-app && npm install`
- [ ] Sprawdź: `npm run dev` → localhost:5173

#### Zadanie 1.2: Zainstaluj główne biblioteki
**Dokumentacja:**
- https://tailwindcss.com/docs/installation/framework-guides
- https://ui.shadcn.com/docs/installation/vite

```bash
npm install axios react-router-dom zustand react-big-calendar dayjs
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
npm install react-hook-form zod @hookform/resolvers
npm install -D vitest @testing-library/react @testing-library/jest-dom
npm install @radix-ui/react-dialog @radix-ui/react-slot clsx tailwind-merge class-variance-authority lucide-react
```

#### Zadanie 1.3: Konfiguracja Tailwind + shadcn/ui
- [ ] Setup `tailwind.config.js`
- [ ] Import Tailwind do `main.css`
- [ ] Zainstaluj komponenty shadcn/ui: `Button`, `Input`, `Card`, `Modal`, `Select`, `Form`

**Commit:** `:tada: feat(setup): React Vite initial setup with Tailwind & shadcn/ui`

---

### SESJA 3-4: Project Structure & Core Setup

#### Zadanie 3.1: Utwórz folder structure
- [ ] Utwórz foldery: `src/components`, `src/pages`, `src/hooks`, `src/services`, `src/store`, `src/utils`
- [ ] Utwórz subfoldery w `components`: `common`, `auth`, `schedule`, `availability`, `reports`, `admin`

#### Zadanie 3.2: Setup App.jsx + Router
**Plik:** `src/App.jsx`

```javascript
import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Schedule from './pages/Schedule';
import Availability from './pages/Availability';
import Reports from './pages/Reports';
import Admin from './pages/Admin';
import ProtectedRoute from './components/auth/ProtectedRoute';
import NotFound from './pages/NotFound';

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route element={<ProtectedRoute />}>
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/schedule" element={<Schedule />} />
          <Route path="/availability" element={<Availability />} />
          <Route path="/reports" element={<Reports />} />
          <Route path="/admin" element={<Admin />} />
        </Route>
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="*" element={<NotFound />} />
      </Routes>
    </Router>
  );
}

export default App;
```

#### Zadanie 3.3: Setup Zustand stores
- [ ] Utwórz `src/store/authStore.js`
- [ ] Utwórz `src/store/scheduleStore.js`
- [ ] Utwórz `src/store/availabilityStore.js`

**Commit:** `:wrench: feat(setup): Project structure & Zustand stores`

---

### SESJA 5-6: Authentication Setup

#### Zadanie 5.1: Utwórz API client
**Plik:** `src/services/api.js`

- [ ] Axios instance z baseURL
- [ ] Request interceptor (dodaj JWT token)
- [ ] Response interceptor (handle 401, redirect to login)

#### Zadanie 5.2: Utwórz authService
**Plik:** `src/services/authService.js`

Metody:
- `login(email, password)` — POST /api/auth/login
- `loginPin(employee_id, pin)` — POST /api/auth/login-pin
- `getCurrentUser()` — GET /api/auth/me

#### Zadanie 5.3: Utwórz useAuth hook
**Plik:** `src/hooks/useAuth.js`

```javascript
import { useCallback } from 'react';
import { useAuthStore } from '../store/authStore';
import { authService } from '../services/authService';

export function useAuth() {
  const { user, token, isAuthenticated, setAuth, logout } = useAuthStore();
  
  const login = useCallback(async (email, password) => {
    const response = await authService.login(email, password);
    localStorage.setItem('token', response.data.token);
    setAuth(response.data.user, response.data.token);
    return response.data;
  }, [setAuth]);
  
  const loginWithPin = useCallback(async (employee_id, pin) => {
    const response = await authService.loginPin(employee_id, pin);
    localStorage.setItem('token', response.data.token);
    setAuth(response.data.user, response.data.token);
    return response.data;
  }, [setAuth]);
  
  const handleLogout = useCallback(() => {
    localStorage.removeItem('token');
    logout();
  }, [logout]);
  
  return { user, token, isAuthenticated, login, loginWithPin, logout: handleLogout };
}
```

**Commit:** `:lock: feat(auth): Auth service & hooks`

---

### SESJA 7-8: Login Components

#### Zadanie 7.1: Utwórz LoginForm component
**Plik:** `src/components/auth/LoginForm.jsx`

- [ ] React Hook Form + Zod validation
- [ ] Email + Password fields
- [ ] Submit button
- [ ] Error messages
- [ ] Loading state
- [ ] Redirect to /dashboard po login

#### Zadanie 7.2: Utwórz PinLoginForm component
**Plik:** `src/components/auth/PinLoginForm.jsx`

- [ ] React Hook Form + Zod validation
- [ ] Employee ID field
- [ ] PIN field (password input)
- [ ] Submit button
- [ ] Error messages
- [ ] Loading state
- [ ] Redirect to /dashboard po login

#### Zadanie 7.3: Utwórz Login page
**Plik:** `src/pages/Login.jsx`

- [ ] Tab view: "Email Login" + "PIN Login"
- [ ] Switch między LoginForm a PinLoginForm
- [ ] Redirect if already authenticated

**Commit:** `:lock: feat(auth): Login forms & page`

---

### SESJA 9-10: Protected Routes & Navigation

#### Zadanie 9.1: Utwórz ProtectedRoute component
**Plik:** `src/components/auth/ProtectedRoute.jsx`

- [ ] Check JWT token (localStorage)
- [ ] If no token: redirect to /login
- [ ] If token: verify with GET /api/auth/me
- [ ] If valid: render Outlet
- [ ] If invalid: redirect to /login

#### Zadanie 9.2: Utwórz Navbar component
**Plik:** `src/components/common/Navbar.jsx`

- [ ] Display user name
- [ ] Display user role
- [ ] Logout button
- [ ] Navigation links (based on role)
- [ ] Responsive design

#### Zadanie 9.3: Utwórz Dashboard page
**Plik:** `src/pages/Dashboard.jsx`

- [ ] Role-based content:
  - Employee: quick actions (add availability, view schedule)
  - Manager: stats (teams, shifts, reports)
  - Admin: stats (employees, schedules, reports)
- [ ] Welcome message

**Commit:** `:shield: feat(auth): Protected routes & navigation`

---

### SESJA 11-12: Schedule Calendar (UI)

#### Zadanie 11.1: Utwórz ScheduleCalendar component
**Plik:** `src/components/schedule/ScheduleCalendar.jsx`

**Dokumentacja:**
- https://jquense.github.io/react-big-calendar/

- [ ] React Big Calendar setup
- [ ] Fetch schedules na initialization
- [ ] Display events (schedules)
- [ ] Drag & drop support (for later)
- [ ] Event colors by position
- [ ] Responsive design

#### Zadanie 11.2: Utwórz ScheduleForm component
**Plik:** `src/components/schedule/ScheduleForm.jsx`

- [ ] React Hook Form + Zod validation
- [ ] Fields: user_id, date, position, shift_start, shift_end, notes
- [ ] Position select (from user.positions)
- [ ] Date picker (Day.js)
- [ ] Time pickers
- [ ] Submit button
- [ ] Error messages

#### Zadanie 11.3: Utwórz Schedule page
**Plik:** `src/pages/Schedule.jsx`

- [ ] Display ScheduleCalendar
- [ ] Button to open ScheduleForm (for manager/admin)
- [ ] Modal for editing schedule
- [ ] Delete button with confirmation

**Commit:** `:calendar: feat(schedule): Schedule calendar & forms`

---

### SESJA 13-14: Availability Manager

#### Zadanie 13.1: Utwórz AvailabilityForm component
**Plik:** `src/components/availability/AvailabilityForm.jsx`

- [ ] React Hook Form + Zod
- [ ] Fields: date, is_available (boolean toggle), notes
- [ ] Date picker
- [ ] Toggle: "Available" / "Unavailable"
- [ ] Notes textarea
- [ ] Submit button

#### Zadanie 13.2: Utwórz AvailabilityCalendar component
**Plik:** `src/components/availability/AvailabilityCalendar.jsx`

- [ ] React Big Calendar lub simple date picker
- [ ] Highlight dates with is_available=false (red)
- [ ] Show notes on hover
- [ ] Click to edit/delete

#### Zadanie 13.3: Utwórz Availability page
**Plik:** `src/pages/Availability.jsx`

- [ ] Display AvailabilityCalendar
- [ ] Button to add new availability
- [ ] Modal with AvailabilityForm
- [ ] List of current availabilities (next 30 days)
- [ ] Delete button

**Commit:** `:calendar: feat(availability): Availability manager`

---

### SESJA 15-16: Reports

#### Zadanie 15.1: Utwórz HoursReport component
**Plik:** `src/components/reports/HoursReport.jsx`

- [ ] Fetch hours report (GET /api/reports/hours/{user_id})
- [ ] Display table:
  - Date, Position, Hours, Notes
- [ ] Total hours per month
- [ ] Total hours per position (chart or table)
- [ ] Filter by month/year

#### Zadanie 15.2: Utwórz PayrollReport component
**Plik:** `src/components/reports/PayrollReport.jsx`

- [ ] Fetch payroll report (GET /api/reports/payroll)
- [ ] Display table:
  - Employee, Hours, Hourly Rate, Total Cost
- [ ] Total cost per employee
- [ ] Total cost per position
- [ ] Grand total
- [ ] Filter by month/year (manager/admin only)

#### Zadanie 15.3: Utwórz Reports page
**Plik:** `src/pages/Reports.jsx`

- [ ] Tab view: "Hours" | "Payroll" | "Coverage"
- [ ] HoursReport component
- [ ] PayrollReport component
- [ ] CoverageReport (simple list: position → count)

**Commit:** `:bar_chart: feat(reports): Reports pages`

---

### SESJA 17-18: Admin Panel (Employees)

#### Zadanie 17.1: Utwórz EmployeeForm component
**Plik:** `src/components/admin/EmployeeForm.jsx`

- [ ] React Hook Form + Zod
- [ ] Fields: name, email, pin, positions (checkboxes), hourly_rate, max_hours_per_month, min_break_hours, contract_type
- [ ] Positions: checkboxes for ["B1", "B2", "PW", "WR", "WS", "TGT", ...]
- [ ] Contract type: select (uop / zlecenie)
- [ ] Submit button
- [ ] Error messages

#### Zadanie 17.2: Utwórz EmployeeList component
**Plik:** `src/components/admin/EmployeeList.jsx`

- [ ] Fetch employees (GET /api/employees)
- [ ] Display table:
  - Name, Email, Positions, Hourly Rate, Status (active/inactive)
- [ ] Edit button → open modal with EmployeeForm
- [ ] Delete button with confirmation
- [ ] Search/filter by name
- [ ] Pagination (optional)

#### Zadanie 17.3: Utwórz EmployeeImport component
**Plik:** `src/components/admin/EmployeeImport.jsx`

- [ ] File input (CSV/Excel)
- [ ] Upload button
- [ ] Validation (file type)
- [ ] Show upload progress
- [ ] Show results: success count + errors

#### Zadanie 17.4: Utwórz Admin page
**Plik:** `src/pages/Admin.jsx`

- [ ] Tab view: "Employees" | "Import"
- [ ] EmployeeList component
- [ ] Button to add new employee
- [ ] Modal with EmployeeForm
- [ ] EmployeeImport component

**Commit:** `:bust_in_silhouette: feat(admin): Admin panel`

---

### SESJA 19-20: Drag & Drop Schedule

#### Zadanie 19.1: Upgrade ScheduleCalendar z drag & drop
**Plik:** `src/components/schedule/ScheduleCalendar.jsx`

**Dokumentacja:**
- https://jquense.github.io/react-big-calendar/

- [ ] Enable dragging
- [ ] On drop: update schedule (PUT /api/schedules/{id})
- [ ] On drop error: show toast notification
- [ ] Optimistic update (update UI immediately, revert on error)
- [ ] Handle time conflicts (show error message)

#### Zadanie 19.2: Utwórz ScheduleEventModal component
**Plik:** `src/components/schedule/ScheduleEventModal.jsx`

- [ ] Display event details on click
- [ ] Edit button → open ScheduleForm
- [ ] Delete button with confirmation
- [ ] Show conflicts warnings

**Commit:** `:star: feat(schedule): Drag & drop schedule`

---

### SESJA 21-22: Error Handling & Loading States

#### Zadanie 21.1: Utwórz useFetch hook
**Plik:** `src/hooks/useFetch.js`

```javascript
export function useFetch(asyncFunction) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const execute = useCallback(async (...args) => {
    setLoading(true);
    setError(null);
    try {
      const result = await asyncFunction(...args);
      setData(result);
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [asyncFunction]);
  
  return { data, loading, error, execute };
}
```

#### Zadanie 21.2: Utwórz LoadingSpinner component
**Plik:** `src/components/common/LoadingSpinner.jsx`

- [ ] Centered spinner
- [ ] Optional message
- [ ] Overlay mode (for full screen)

#### Zadanie 21.3: Utwórz ErrorBoundary component
**Plik:** `src/components/common/ErrorBoundary.jsx`

- [ ] Catch React errors
- [ ] Display error message
- [ ] Retry button

**Commit:** `:gear: feat(error-handling): Error boundaries & loading states`

---

### SESJA 23-24: Notifications & Toast Messages

#### Zadanie 23.1: Utwórz Toast context/store
**Plik:** `src/store/toastStore.js` (lub use react-toastify)

```javascript
create((set) => ({
  toasts: [],
  addToast: (message, type = 'info') => {
    const id = Date.now();
    set((state) => ({ toasts: [...state.toasts, { id, message, type }] }));
    setTimeout(() => {
      set((state) => ({ toasts: state.toasts.filter(t => t.id !== id) }));
    }, 3000);
  },
  removeToast: (id) => set((state) => ({ toasts: state.toasts.filter(t => t.id !== id) })),
}))
```

#### Zadanie 23.2: Utwórz ToastContainer component
**Plik:** `src/components/common/ToastContainer.jsx`

- [ ] Display toasts (top-right corner)
- [ ] Auto-dismiss after 3s
- [ ] Different styles for success/error/warning/info
- [ ] Close button

#### Zadanie 23.3: Zainstaluj react-toastify (alternative)
**Dokumentacja:**
- https://fkhadra.github.io/react-toastify/introduction

- [ ] `npm install react-toastify`
- [ ] Import `ToastContainer` w App.jsx
- [ ] Use `toast()` function w components

**Commit:** `:bell: feat(notifications): Toast messages`

---

### SESJA 25-26: Tests

#### Zadanie 25.1: Setup Vitest + RTL
**Plik:** `tests/setup.js`

```javascript
import { expect, afterEach, vi } from 'vitest';
import { cleanup } from '@testing-library/react';
import '@testing-library/jest-dom';

afterEach(() => cleanup());
```

**vite.config.js update:**
```javascript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    setupFiles: './tests/setup.js',
  }
})
```

#### Zadanie 25.2: Test LoginForm component
**Plik:** `tests/components/LoginForm.test.jsx`

- [ ] Render form
- [ ] Test form submission
- [ ] Test validation errors
- [ ] Test successful login (mock API)
- [ ] Test redirect after login

```javascript
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import LoginForm from '@/components/auth/LoginForm';
import { authService } from '@/services/authService';

vi.mock('@/services/authService');

test('should submit form with email and password', async () => {
  authService.login.mockResolvedValueOnce({
    data: { token: 'fake-token', user: { id: 1, name: 'John' } }
  });
  
  render(
    <BrowserRouter>
      <LoginForm />
    </BrowserRouter>
  );
  
  await userEvent.type(screen.getByPlaceholderText(/email/i), 'john@example.com');
  await userEvent.type(screen.getByPlaceholderText(/password/i), 'password123');
  await userEvent.click(screen.getByRole('button', { name: /sign in/i }));
  
  await waitFor(() => {
    expect(authService.login).toHaveBeenCalledWith('john@example.com', 'password123');
  });
});
```

#### Zadanie 25.3: Test ScheduleCalendar component
**Plik:** `tests/components/ScheduleCalendar.test.jsx`

- [ ] Render calendar
- [ ] Test fetch schedules on mount
- [ ] Test drag & drop
- [ ] Test click event
- [ ] Test loading state

#### Zadanie 25.4: Run tests
- [ ] `npm run test` (Vitest watch mode)
- [ ] `npm run test:ui` (Vitest UI)

**Commit:** `:test_tube: test(components): Unit tests for components`

---

### SESJA 27-28: Responsive Design & Mobile

#### Zadanie 27.1: Mobile-first CSS
- [ ] Review all components for mobile screens
- [ ] Test on actual mobile device or DevTools
- [ ] Update components with Tailwind responsive classes (`sm:`, `md:`, `lg:`)

#### Zadanie 27.2: Adjust calendar for mobile
**Plik:** `src/components/schedule/ScheduleCalendar.jsx`

- [ ] Reduce calendar size on mobile
- [ ] Alternative view: list view for mobile (instead of calendar)
- [ ] Touch-friendly drag & drop

#### Zadanie 27.3: Optimize images & assets
- [ ] Compress images
- [ ] Use SVG for icons
- [ ] Lazy load images

**Commit:** `:iphone: feat(mobile): Mobile-first responsive design`

---

### SESJA 29-30: Documentation & Deployment

#### Zadanie 29.1: Utwórz README.md
**Plik:** `README.md`

README powinno zawierać:

1. **Project overview** — co to jest, dla kogo
2. **Tech stack** — React, Vite, Tailwind, etc.
3. **Quick start:**
   - Clone repo
   - `npm install`
   - `cp .env.example .env` (update VITE_API_URL)
   - `npm run dev` → localhost:5173
4. **Project structure** — opis folderów
5. **Components** — lista głównych komponentów
6. **Hooks** — custom hooks
7. **Services** — API integration
8. **Testing** — jak uruchomić testy
9. **Build & Deploy** — `npm run build`
10. **Environment variables** — VITE_API_URL, etc.

#### Zadanie 29.2: Environment variables
**Plik:** `.env.example`

```
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=Schedule Manager
```

#### Zadanie 29.3: Build & Deployment
- [ ] `npm run build` → production build
- [ ] Test build locally: `npm run preview`
- [ ] Deploy to Vercel, Netlify, or own server

**Commit:** `:memo: docs(readme): Frontend documentation & build guide`

---

## 9. ESTYMACJA CZASU (dla zaawansowanego w ReactJS)

| Sesja | Faza | Zadania | Estymacja |
|-------|------|---------|-----------|
| 1-2 | Setup | Vite, React, Tailwind, shadcn/ui | 4h |
| 3-4 | Architecture | Folder structure, Zustand setup | 3h |
| 5-6 | Auth Setup | API client, authService, useAuth | 4h |
| 7-8 | Login | LoginForm, PinLoginForm | 4h |
| 9-10 | Auth UI | ProtectedRoute, Navbar, Dashboard | 4h |
| 11-12 | Schedule Calendar | React Big Calendar, forms | 6h |
| 13-14 | Availability | AvailabilityForm, calendar | 4h |
| 15-16 | Reports | HoursReport, PayrollReport | 5h |
| 17-18 | Admin | EmployeeList, forms, import | 5h |
| 19-20 | Drag & Drop | Schedule drag & drop, modals | 4h |
| 21-22 | Error Handling | useFetch, ErrorBoundary, loading | 3h |
| 23-24 | Notifications | Toast messages | 2h |
| 25-26 | Tests | Vitest, component tests | 5h |
| 27-28 | Mobile | Responsive design, optimization | 4h |
| 29-30 | Docs | README, deployment | 3h |
| — | Buffer | Bugfixes, debugging, refinement | 4h |
| | **TOTAL** | | **~64h** |

**Realistycznie: ~2-3 tygodnie (3-4 sesje/dzień × 5-6 dni/tydzień)**

---

## 10. SCHEMAT COMMITOWANIA

Dla każdej sesji:
- Atomic commit po ukończeniu zadań
- Format: `:emoji: type(scope): subject`
- Subject = krótko co zrobiłeś

Przykłady:
```
:tada: feat(setup): React Vite initial setup
:wrench: feat(setup): Project structure & Zustand stores
:lock: feat(auth): Auth service & hooks
:lock: feat(auth): Login forms & page
:shield: feat(auth): Protected routes & navigation
:calendar: feat(schedule): Schedule calendar & forms
:calendar: feat(availability): Availability manager
:bar_chart: feat(reports): Reports pages
:bust_in_silhouette: feat(admin): Admin panel
:star: feat(schedule): Drag & drop
:gear: feat(error-handling): Error boundaries & loading
:bell: feat(notifications): Toast messages
:test_tube: test(components): Unit tests
:iphone: feat(mobile): Responsive design
:memo: docs(readme): Frontend documentation
```

---

## 11. ENVIRONMENT VARIABLES

**Plik:** `.env.local`

```
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=Schedule Manager - Kopalnia Soli Wieliczka
VITE_APP_VERSION=1.0.0
```

---

## 12. KEY LIBRARIES & VERSIONS (Recommended)

```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.x",
    "axios": "^1.6.0",
    "zustand": "^4.4.0",
    "react-big-calendar": "^1.8.0",
    "dayjs": "^1.11.0",
    "react-hook-form": "^7.47.0",
    "zod": "^3.22.0",
    "@hookform/resolvers": "^3.3.0",
    "tailwindcss": "^3.3.0",
    "react-toastify": "^9.1.0",
    "@radix-ui/react-dialog": "^1.1.0"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.1.0",
    "vite": "^5.0.0",
    "vitest": "^0.34.0",
    "@testing-library/react": "^14.0.0",
    "@testing-library/jest-dom": "^6.1.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0"
  }
}
```

---

## 13. PODSUMOWANIE

- **Estymacja:** ~64 godziny (30 sesji po 2h)
- **Stack:** React 18 + Vite + Zustand + Tailwind + shadcn/ui
- **Approach:** TDD-style — komponenty, hooki, serwisy z dokumentacją
- **Struktura:** 30 sesji, każda atomic + commit
- **Focus:** Nauczenie się zamiast copy-paste, best practices React
- **Quality:** Unit testy, error handling, responsive design, a11y

**Ty samy napiszesz kod, będziesz rozumieć każdy kawałek, i nauczysz się React na praktyce!** 🚀

Powodzenia! Jeśli będziesz miał pytania — pytaj!
