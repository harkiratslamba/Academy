# Auth Module — MVC Flow Documentation

**Location:** `Modules/Auth/`  
**Purpose:** Handles all authentication — login, logout, session management, and role-based access control.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Auth/app/Http/Controllers/AuthController.php |
| Middleware | Modules/Auth/app/Http/Middleware/RoleMiddleware.php |
| Routes | Modules/Auth/routes/web.php |
| View | Modules/Auth/resources/views/login.blade.php |
| Model | App/Models/User (shared app model) |
| DB Table | users |

---

## Flow 1: Show Login Page

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/` (root, redirects here) or `/login` |
| Route Name | login |
| Controller@Method | AuthController@showLogin |
| View | Modules/Auth/resources/views/login.blade.php |
| Middleware | guest (redirects to dashboard if already logged in) |

**What happens:**
1. If user is already logged in → redirected to their dashboard (admin→/admin/dashboard, teacher→/teacher/dashboard)
2. If not logged in → render login.blade.php with Bootstrap 5 form

**Data passed to view:** None (static form)

**View features:**
- Username and password fields
- Password show/hide toggle (JavaScript)
- Remember me checkbox
- Demo credentials hint (admin/admin123, teacher/teacher123)
- Error alert if login failed

---

## Flow 2: Submit Login Form

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/login` |
| Route Name | login.submit |
| Controller@Method | AuthController@login |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| username | Yes | required | users | username |
| password | Yes | required | users | password (bcrypt compared) |
| remember | No | checkbox | (sets session cookie) | - |

**Business Logic (step by step):**
1. Validate: username and password required
2. `Auth::attempt(['username' => $request->username, 'password' => $request->password], $request->boolean('remember'))`
3. If attempt fails → `redirect()->back()->with('error', 'Invalid username or password')`
4. Check `auth()->user()->status` → if false (disabled): logout and return error "Your account has been disabled"
5. Update `users.last_login = now()` for the authenticated user
6. Redirect based on role:
   - admin or coordinator → `/admin/dashboard`
   - teacher → `/teacher/dashboard`
   - default → `/admin/dashboard`

**DB Tables Read:** users (username + password check)  
**DB Tables Written:** users (last_login column)  
**Models Used:** App\Models\User (via Laravel Auth facade)

---

## Flow 3: Logout

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/logout` |
| Route Name | logout |
| Controller@Method | AuthController@logout |
| Middleware | auth |

**Business Logic:**
1. `Auth::logout()`
2. `$request->session()->invalidate()`
3. `$request->session()->regenerateToken()`
4. Redirect to `/login`

**DB Tables Written:** sessions table (session destroyed)

---

## Role Middleware

**File:** `Modules/Auth/app/Http/Middleware/RoleMiddleware.php`  
**Alias:** `role` (registered in `bootstrap/app.php`)

**Usage in routes:**
```php
->middleware(['auth', 'role:admin,coordinator'])  // admin pages
->middleware(['auth', 'role:teacher'])             // teacher portal
```

**Logic:**
1. If not authenticated → `redirect()->route('login')`
2. Get allowed roles from middleware parameters: `role:admin,coordinator` → ['admin', 'coordinator']
3. If `auth()->user()->role` NOT in allowed roles → `abort(403)`
4. Otherwise → proceed to controller

**Role values in DB:** admin, teacher, coordinator

---

## Login Flow Diagram

```
User visits any page
        │
        ▼
  Is user logged in?
        │
   NO ──┤──► Redirect to /login
        │
   YES──┤──► role middleware check
        │
        ▼
/login page shown
        │
User fills username + password + submits
        │
        ▼
POST /login → AuthController@login
        │
        ▼
Auth::attempt(['username' => ..., 'password' => ...])
        │
 FAIL ──┤──► Back with error "Invalid credentials"
        │
 OK ────┤──► Check user.status
        │
  DISABLED ─► Logout + error "Account disabled"
        │
  ACTIVE ──► Update last_login
        │
        ▼
Check user.role
        │
   admin/coordinator ──► /admin/dashboard
        │
   teacher ──────────► /teacher/dashboard
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Route definitions | Modules/Auth/routes/web.php |
| Controller | Modules/Auth/app/Http/Controllers/AuthController.php |
| Middleware | Modules/Auth/app/Http/Middleware/RoleMiddleware.php |
| Middleware registration | bootstrap/app.php |
| Login view | Modules/Auth/resources/views/login.blade.php |
| Model | App/Models/User |
| DB Table read | users (username, password, role, status) |
| DB Table written | users (last_login), sessions |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add new role (e.g., principal) | users ENUM migration, RoleMiddleware, AuthController@redirectByRole, all route middleware groups |
| Change login field from username to email | AuthController@login Auth::attempt call, login.blade.php form field name |
| Add 2FA | AuthController@login, add new view, possibly new middleware |
| Disable remember-me | Remove checkbox from login.blade.php, remove $remember param from Auth::attempt |
