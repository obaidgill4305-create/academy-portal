# Fellowship Academy Portal (v2)

Ye ab ek **poora professional institute portal** hai:
- Public website (Home, About, Academic Classes Nursery–12, Skill Classes, Admissions, Contact/Enquiry form)
- Secure Admin Login
- Admin Panel: Admissions (Academic + Skill), Attendance, Fees, Enquiries, ID Cards, Certificates

PHP + MySQL par bana hai. Do tareeqon se chala sakte hain: **(A) apne computer pe (XAMPP)** testing/local use ke liye, ya **(B) Render.com pe live** puri duniya ke liye.

---

## A) Local (XAMPP) Setup

### 1. XAMPP install karein
https://www.apachefriends.org se download karein.

### 2. Files sahi jagah rakhein
Poora `academy-portal` folder copy karke XAMPP ke `htdocs` me paste karein:
- Windows: `C:\xampp\htdocs\academy-portal`
- Mac: `/Applications/XAMPP/htdocs/academy-portal`

### 3. XAMPP start karein
XAMPP Control Panel me **Apache** aur **MySQL** dono "Start" karein.

### 4. Database banayein
1. Browser me `http://localhost/phpmyadmin` kholein
2. "SQL" tab par click karein
3. **Naya install hai** → `schema.sql` ka pura content paste kar ke "Go" dabayein
4. **Pehle se purana portal chal raha tha (upgrade kar rahe hain)** → iski jagah `migrate_v2.sql` paste kar ke "Go" dabayein (isse purana data safe rehta hai)

### 5. Portal kholein
```
http://localhost/academy-portal/
```
(index.html ko seedha double-click mat karein — Apache ke through hi chalega)

### 6. Pehli dafa login karein
- Username: `admin`
- Password: `admin123`

**Login ke foran baad Sidebar → "Change Password" se password badal dein.**

---

## B) Live Deployment on Render

Render par PHP app run karne ke liye Docker use hota hai (is folder me `Dockerfile` already maujood hai). Render khud se koi free MySQL nahi deta, isliye ek chhota **external MySQL database** lena hoga — ye bhi free milta hai. Steps:

### Step 1 — Free MySQL database lein
In me se koi ek use kar sakte hain (dono free tier dete hain):
- **Railway** (railway.app) → "New Project" → "Provision MySQL"
- **Aiven** (aiven.io) → Free MySQL plan

Database create hone ke baad aapko ye 5 cheezein milengi: **Host, Port, Database Name, Username, Password**. Inhe kahin note kar lein.

### Step 2 — Database me tables banayein
Us database provider ke apne SQL console/phpMyAdmin-jaisay tool me `schema.sql` file ka content paste karke run karein (agar fresh install hai). Ye `students`, `attendance`, `fees`, `admins`, `enquiries` tables bana dega.

### Step 3 — Ye code GitHub par push karein
Render, GitHub repo se deploy karta hai. Is poore `academy-portal` folder ko ek naye GitHub repository me push kar dein.

### Step 4 — Render par Web Service banayein
1. render.com par account banayein, "New" → "Web Service"
2. Apna GitHub repo select karein
3. **Environment**: "Docker" select karein (Dockerfile is repo me already hai, Render use khud pehchan lega)
4. **Environment Variables** add karein (Step 1 ki details se):
   - `DB_HOST` = aapka MySQL host
   - `DB_PORT` = aapka MySQL port (aksar `3306`)
   - `DB_NAME` = database ka naam
   - `DB_USER` = username
   - `DB_PASS` = password
5. "Create Web Service" dabayein — Render build karega aur ek public URL dega, jaise `https://fellowship-academy.onrender.com`

### Step 5 — Test karein
Us URL ko browser me kholein → public website dikhni chahiye. Phir "Admin Login" se `admin` / `admin123` se login karein aur **foran password change kar dein**.

> **Note:** Render ki free web service kuch der inactive rehne par "sleep" ho jaati hai aur agli request par dobara start hone me 30-60 second lagta hai — ye normal hai, free tier ki limitation hai.

---

## Naye Features (v2)

| Feature | Kahan |
|---|---|
| Public website (Home/About/Programs/Contact) | Login se pehle dikhta hai |
| Admin Login + Change Password | Sidebar |
| Academic Classes | Nursery se Class 12 tak (11 & 12 me stream bhi: Pre-Medical, Pre-Engineering, ICS, Commerce, General) |
| Skill Classes | AI & Computer Skills, Web & App Development, Graphic Designing & Video Editing, Spoken English & Freelancing |
| Batch tracking | Skill Class admission form me Batch, Start Date, End Date, Status |
| ID Cards | Students page → "ID Card" button (print-ready) |
| Certificates | Completed skill-course students → "Certificate" button (print-ready) |
| Enquiries | Public "Contact" form submissions, admin panel me "Enquiries" page |

## Public site ka apna content update karna
`index.html` me `id="contact"` section ke andar contact info (address/phone/email) abhi placeholder hai — apni real details se badal dein. Skill course descriptions/duration `SKILL_COURSES` JavaScript array me (`index.html` ke `<script>` section ke shuru me) hain, wahan se edit kar sakte hain.

## Agar "Database connection failed" ka error aaye
- Local: MySQL XAMPP panel me chal raha hai? `schema.sql` run ho chuka?
- Live: Environment Variables (DB_HOST/DB_NAME/DB_USER/DB_PASS/DB_PORT) sahi hain? Database provider ki firewall/"allow external connections" setting on hai?

## Data ka backup
phpMyAdmin (ya jo bhi database tool) se `academy_portal` database "Export" kar ke `.sql` file backup le sakte hain — chahe local ho ya live.
