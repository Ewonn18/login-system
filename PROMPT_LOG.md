# Prompt Log

## Task 1
Prompt: Review my simple PHP login system from frontend to backend. Explain the current flow, identify the top 3 weak spots, recommend one small full-stack improvement, list the files to change, and give a manual test checklist. Do not edit any code yet.
Result:The agent explained the signup, signin, dashboard, and logout flow. It identified three weak spots: hardcoded DB password, missing CSRF protection, and login error messages that reveal whether an email exists. It recommended adding CSRF protection as one small frontend-to-backend improvement.
Files changed: None yet
What I tested manually: None yet

## Task 2
Prompt:
Add CSRF protection to my login and sign-up flow using minimal safe changes in index.php, login.php, and register.php.

Result:
The agent added CSRF token generation in the frontend forms and backend token validation in login.php and register.php.

Files changed:
- index.php
- login.php
- register.php

What I tested manually:
- Normal sign up works
- Normal sign in works
- Dashboard opens only after login
- Logout works
- Invalid CSRF token is rejected on sign in
- Invalid CSRF token is rejected on sign up