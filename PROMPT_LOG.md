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

## Task 3

Prompt:
Add a simple community sharing feature for logged-in users. Create community.php, update dashboard.php, and use a posts table in MySQL.

Result:
The agent created a protected community page where logged-in users can share their IT journey, knowledge, and tips. It also updated the dashboard to link to the community page.

Files changed:

- community.php
- dashboard.php

What I tested manually:

- logged-in users can access the community page
- users can create a post
- posts appear in the list
- empty fields are rejected
- logged-out users are redirected away from community.php

## Task 4

Prompt:
Improve the profile system by adding more user information fields like bio, school, specialization, and skills using edit-profile.php and dashboard.php.

Result:
The agent updated the profile system so users can add and edit more personal information, and the dashboard now shows those details.

Files changed:

- edit-profile.php
- dashboard.php

What I tested manually:

- logged-in user can update bio
- logged-in user can update school
- logged-in user can update specialization
- logged-in user can update skills
- updated profile information appears on the dashboard

## Task 5

Prompt:
Redesign and rebrand index.php for TechTrail Community. Improve the branding, wording, responsiveness, and visual design. Make it more modern, technology-inspired, and presentation-ready. Also fix the social icons so they are not fake clickable links.

Result:
The landing page was redesigned with the TechTrail Community branding. The sign-in and sign-up section now has a cleaner technology-inspired UI, improved wording, better spacing, and a more professional identity. The page now feels more like an IT student/developer community platform instead of a generic login system.

Files changed:

- index.php

What I tested manually:

- sign in form still works
- sign up navigation still works
- page branding now shows TechTrail / TechTrail Community
- layout looks cleaner on desktop
- social icons are no longer misleading or broken
- index page looks more presentation-ready

## Task 6

Prompt:
Add a simple profile badge or specialization tag system for TechTrail Community. Reuse the current profile system if possible, keep it beginner-friendly, and display the badge in the profile/dashboard.

Result:
The agent reused the existing specialization field as a curated profile badge system. In edit-profile.php, the specialization field was changed into a dropdown with badge options such as Frontend Learner, Backend Explorer, Networking Enthusiast, UI/UX Beginner, and Career Builder. In dashboard.php, the specialization is now displayed as a styled tech-themed badge pill with a fallback if no badge is selected.

Files changed:

- edit-profile.php
- dashboard.php

What I tested manually:

- user can select a badge in edit-profile.php
- selected badge is saved successfully
- badge appears in dashboard.php
- existing profile update flow still works
- login/session flow is not broken

## Task 7

Prompt:
Allow users to edit and delete only their own posts in TechTrail Community.

Result:
The agent added post ownership controls so only the author of a post can edit or delete it. Clear edit and delete actions were added while keeping the current posts and comments system working.

Files changed:

- community.php
- edit-post.php
- delete-post.php
- (or whichever files were actually changed)

What I tested manually:

- author can see edit and delete buttons
- author can edit their own post
- author can delete their own post
- other users cannot edit or delete someone else’s post
- comments still work
