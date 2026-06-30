<?php
include 'database.php';

$texts = [
    'en' => [
        'title'    => 'Version Log - Future X',
        'heading'  => 'Version Log',
        'subtitle' => 'A record of updates and improvements to this website.',
        'new'      => 'New',
        'fix'      => 'Fix',
        'improve'  => 'Improved',
    ],
    'th' => [
        'title'    => 'ประวัติการอัปเดต - Future X',
        'heading'  => 'ประวัติการอัปเดต',
        'subtitle' => 'บันทึกการอัปเดตและการปรับปรุงเว็บไซต์นี้',
        'new'      => 'ใหม่',
        'fix'      => 'แก้ไข',
        'improve'  => 'ปรับปรุง',
    ],
];
$t = $texts[$lang] ?? $texts['en'];

// Version entries — newest first.
// Each entry: version, date (YYYY-MM-DD), changes array with [type, en, th]
// type: 'new' | 'fix' | 'improve'
$versions = [
    [
        'version' => 'V21.3C',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new',     'en' => 'Reset password page: hold-to-show eye buttons added to both password fields.', 'th' => 'หน้ารีเซ็ตรหัสผ่าน: เพิ่มปุ่มตาแสดงรหัสผ่านชั่วคราวในทั้งสองช่อง'],
            ['type' => 'fix',     'en' => 'Settings page: eye buttons are now perfectly centered vertically inside the password input fields.', 'th' => 'หน้าการตั้งค่า: แก้ไขปุ่มตาให้อยู่ตรงกลางแนวตั้งของช่องกรอกรหัสผ่านแล้ว'],
        ],
    ],
    [
        'version' => 'V21.3B',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Reset password page now shows the same live password requirements checklist as the other password forms.', 'th' => 'หน้ารีเซ็ตรหัสผ่านแสดงรายการข้อกำหนดรหัสผ่านแบบเรียลไทม์เหมือนกับแบบฟอร์มอื่นๆ แล้ว'],
            ['type' => 'improve', 'en' => 'Reset password page now enforces the same 6–12 character limit as all other password forms.', 'th' => 'หน้ารีเซ็ตรหัสผ่านบังคับใช้จำกัด 6–12 ตัวอักษรเหมือนกับแบบฟอร์มอื่นๆ แล้ว'],
        ],
    ],
    [
        'version' => 'V21.3A',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'improve', 'en' => 'Password rule "At least one letter" updated to "At least one lowercase letter" — applies to registration, settings, admin users, and password reset pages.', 'th' => 'ข้อกำหนดรหัสผ่าน "มีตัวอักษรอย่างน้อย 1 ตัว" เปลี่ยนเป็น "มีตัวพิมพ์เล็กอย่างน้อย 1 ตัว" — ใช้กับหน้าสมัคร, การตั้งค่า, แอดมินผู้ใช้ และการรีเซ็ตรหัสผ่าน'],
        ],
    ],
    [
        'version' => 'V21.3',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Settings page: the Change Password form now shows a live password requirements checklist — same animated dot list as on the registration and admin users pages.', 'th' => 'หน้าการตั้งค่า: แบบฟอร์มเปลี่ยนรหัสผ่านแสดงรายการข้อกำหนดรหัสผ่านแบบเรียลไทม์ — รายการจุดเหมือนกับในหน้าสมัครสมาชิกและหน้าแอดมิน'],
            ['type' => 'improve', 'en' => 'Password rules on the settings page now match registration: 6–12 characters, at least one number, one letter, and one capital letter.', 'th' => 'ข้อกำหนดรหัสผ่านในหน้าการตั้งค่าตอนนี้ตรงกับหน้าสมัคร: 6–12 ตัวอักษร มีตัวเลข ตัวอักษร และตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว'],
        ],
    ],
    [
        'version' => 'V21.2',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'improve', 'en' => 'Navigation bar: "Updates" link now appears before "About Us" and "Sources".', 'th' => 'แถบนำทาง: ลิงก์ "อัปเดต" แสดงก่อน "เกี่ยวกับเรา" และ "แหล่งที่มา"'],
            ['type' => 'improve', 'en' => 'Admin panel: "Main Page" in the profile menu is now highlighted in blue and hidden from employee-admin accounts.', 'th' => 'แผงแอดมิน: "เว็บหลัก" ในเมนูโปรไฟล์แสดงเป็นสีน้ำเงินและซ่อนจากบัญชีแอดมินพนักงาน'],
            ['type' => 'improve', 'en' => 'Admin panel: bottom spacing on the dashboard and Admin Users page reduced to a tighter 30 px.', 'th' => 'แผงแอดมิน: ลดระยะห่างด้านล่างของหน้า Dashboard และ Admin Users เหลือ 30 px'],
        ],
    ],
    [
        'version' => 'V21.1',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'improve', 'en' => 'Redesigned the phone number input on the registration and admin users pages — replaced the clunky dropdown button with a clean unified field: flag + code on the left, a subtle divider, and the number input on the right.', 'th' => 'ออกแบบช่องกรอกเบอร์โทรศัพท์ใหม่ในหน้าสมัครสมาชิกและหน้าแอดมิน — เปลี่ยนปุ่มเมนูรหัสประเทศแบบเก่าเป็นช่องกรอกแบบรวมที่สวยงาม: ธงและรหัสทางซ้าย เส้นคั่น และช่องกรอกเบอร์ทางขวา'],
        ],
    ],
    [
        'version' => 'V21.0A',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'Phone number is now pulled automatically from the user\'s account instead of being asked again at checkout.', 'th' => 'เบอร์โทรศัพท์ถูกดึงจากบัญชีผู้ใช้โดยอัตโนมัติ ไม่ต้องกรอกซ้ำในหน้าชำระเงิน'],
        ],
    ],
    [
        'version' => 'V21.0',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Phone number field added to checkout — collected from customers and saved with each order.', 'th' => 'เพิ่มช่องเบอร์โทรศัพท์ในหน้าชำระเงิน — รวบรวมจากลูกค้าและบันทึกพร้อมกับแต่ละคำสั่งซื้อ'],
            ['type' => 'new', 'en' => 'Admin order notification emails now include the customer\'s phone number.', 'th' => 'อีเมลแจ้งเตือนคำสั่งซื้อสำหรับแอดมินแสดงเบอร์โทรศัพท์ของลูกค้าแล้ว'],
            ['type' => 'new', 'en' => 'Admin order detail modal now shows the customer\'s phone number as a clickable call link; phone is also searchable in the order search box.', 'th' => 'หน้าต่างรายละเอียดคำสั่งซื้อในแผงแอดมินแสดงเบอร์โทรของลูกค้าเป็นลิงก์โทรออก และค้นหาด้วยเบอร์โทรได้ในช่องค้นหา'],
        ],
    ],
    [
        'version' => 'V20.3',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'Employee-admin accounts now require OTP verification when logging in, the same as regular users.', 'th' => 'บัญชีแอดมินพนักงานต้องยืนยัน OTP เมื่อเข้าสู่ระบบ เช่นเดียวกับผู้ใช้ทั่วไป'],
        ],
    ],
    [
        'version' => 'V20.2',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'improve', 'en' => 'Add Admin form: all fields are now required, phone number field uses the same country code dropdown as the registration page (+66/+60/+856).', 'th' => 'ฟอร์มเพิ่มแอดมิน: ทุกช่องต้องกรอก และช่องเบอร์โทรใช้เมนูรหัสประเทศเหมือนกับหน้าสมัครสมาชิก (+66/+60/+856)'],
        ],
    ],
    [
        'version' => 'V20.1A',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed crash on Admin Users page caused by attempting to add a primary key that already existed on the admins table.', 'th' => 'แก้ไขข้อผิดพลาดในหน้า Admin Users ที่เกิดจากการพยายามเพิ่ม primary key ซ้ำในตาราง admins'],
        ],
    ],
    [
        'version' => 'V20.1',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix',     'en' => 'Employee-admin accounts can now log in through the normal login page — no separate admin login page needed.', 'th' => 'บัญชีแอดมินพนักงานสามารถเข้าสู่ระบบผ่านหน้าเข้าสู่ระบบปกติได้แล้ว ไม่จำเป็นต้องใช้หน้าเข้าสู่ระบบแยกต่างหาก'],
            ['type' => 'fix',     'en' => 'Fixed error when creating admin accounts caused by missing AUTO_INCREMENT on the admins table.', 'th' => 'แก้ไขข้อผิดพลาดขณะสร้างบัญชีแอดมิน เนื่องจาก AUTO_INCREMENT ขาดหายในตาราง admins'],
            ['type' => 'improve', 'en' => 'Add Admin form now uses the same password rules and live checklist as the registration page (6–12 characters, must include number, letter, and capital).', 'th' => 'ฟอร์มเพิ่มแอดมินใช้กฎรหัสผ่านและรายการตรวจสอบแบบสดเหมือนกับหน้าสมัครสมาชิก (6–12 ตัวอักษร ต้องมีตัวเลข ตัวอักษร และตัวพิมพ์ใหญ่)'],
        ],
    ],
    [
        'version' => 'V20.0',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Super-admin can now add and manage employee admin accounts from a new Admin Users page.', 'th' => 'แอดมินหลักสามารถเพิ่มและจัดการบัญชีแอดมินพนักงานได้จากหน้า Admin Users ใหม่'],
            ['type' => 'new', 'en' => 'Employee admins can log in and access the admin panel with their own credentials.', 'th' => 'แอดมินพนักงานสามารถเข้าสู่ระบบและเข้าถึงแผงแอดมินด้วยข้อมูลรับรองของตนเองได้'],
        ],
    ],
    [
        'version' => 'V19.3',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'View Slip link in order notification emails now opens correctly instead of producing a broken double URL.', 'th' => 'ลิงก์ดูสลิปในอีเมลแจ้งเตือนคำสั่งซื้อตอนนี้เปิดได้ถูกต้องแล้ว ไม่เกิด URL ซ้ำซ้อนอีกต่อไป'],
        ],
    ],
    [
        'version' => 'V19.2',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'Checkout no longer allows selecting a pick-up time that has already passed when today\'s date is chosen.', 'th' => 'หน้าชำระเงินไม่อนุญาตให้เลือกเวลานัดรับที่ผ่านไปแล้วเมื่อเลือกวันที่เป็นวันนี้'],
            ['type' => 'fix', 'en' => 'If all pick-up time slots for today have passed, today is automatically disabled in the date picker.', 'th' => 'หากเวลานัดรับทุกช่องของวันนี้ผ่านไปแล้ว วันนี้จะถูกปิดการใช้งานในตัวเลือกวันที่โดยอัตโนมัติ'],
        ],
    ],
    [
        'version' => 'V19.1',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'Product images uploaded via the admin panel now display correctly on the customer products page.', 'th' => 'รูปภาพสินค้าที่อัปโหลดผ่านหน้าแอดมินแสดงผลได้ถูกต้องในหน้าสินค้าของลูกค้า'],
        ],
    ],
    [
        'version' => 'V19',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Products page now uses a quantity stepper directly on each product card — tap + to add, − to decrease, and the trash icon to remove. No more separate Add to Cart button.', 'th' => 'หน้าสินค้าตอนนี้มีปุ่มเพิ่ม/ลดจำนวนบนการ์ดสินค้าโดยตรง กด + เพื่อเพิ่ม กด − เพื่อลด และกดไอคอนถังขยะเพื่อลบออก ไม่มีปุ่ม "เพิ่มลงตะกร้า" อีกต่อไป'],
        ],
    ],
    [
        'version' => 'V18.2',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Admin can now manually set a product ID when adding a new product.', 'th' => 'แอดมินสามารถกำหนด ID สินค้าเองได้เมื่อเพิ่มสินค้าใหม่'],
        ],
    ],
    [
        'version' => 'V18.1',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Added Contact Us section to the About Us page with phone numbers and email.', 'th' => 'เพิ่มส่วน "ติดต่อเรา" ในหน้าเกี่ยวกับเรา พร้อมหมายเลขโทรศัพท์และอีเมล'],
        ],
    ],
    [
        'version' => 'V18',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Admin Products page: add, edit, and delete products including name, SKU, price, PV, units, and image.', 'th' => 'หน้าจัดการสินค้าสำหรับแอดมิน: เพิ่ม แก้ไข และลบสินค้า รวมถึงชื่อ, SKU, ราคา, PV, หน่วย และรูปภาพ'],
        ],
    ],
    [
        'version' => 'V17',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new', 'en' => 'Added Version Log page so customers can see what has changed.', 'th' => 'เพิ่มหน้าประวัติการอัปเดต เพื่อให้ลูกค้าสามารถดูสิ่งที่เปลี่ยนแปลงได้'],
            ['type' => 'new', 'en' => '"Updates" link added to the navigation bar.', 'th' => 'เพิ่มลิงก์ "อัปเดต" ในแถบเมนูนำทาง'],
        ],
    ],
    [
        'version' => 'V16.1A',
        'date'    => '2025-06-30',
        'changes' => [
            ['type' => 'improve', 'en' => 'Pickup time display uses Thai "น." format and is language-aware (EN/TH).', 'th' => 'เวลานัดรับแสดงในรูปแบบ "น." สำหรับภาษาไทย และรองรับการแสดงผลสองภาษา (EN/TH)'],
            ['type' => 'improve', 'en' => 'Pickup time range restricted to 10:00 AM – 5:00 PM.', 'th' => 'ช่วงเวลานัดรับจำกัดระหว่าง 10:00 น. – 17:00 น.'],
        ],
    ],
    [
        'version' => 'V16.1',
        'date'    => '2025-06-29',
        'changes' => [
            ['type' => 'new', 'en' => 'Added pickup appointment time selection to the checkout flow.', 'th' => 'เพิ่มการเลือกเวลานัดรับสินค้าในขั้นตอนชำระเงิน'],
            ['type' => 'new', 'en' => 'Added store location display on the checkout page.', 'th' => 'เพิ่มการแสดงที่ตั้งร้านในหน้าชำระเงิน'],
        ],
    ],
];

// Tag badge colors
$tagColors = [
    'new'     => ['bg' => '#DCFCE7', 'text' => '#15803D'],
    'fix'     => ['bg' => '#FEF3C7', 'text' => '#92400E'],
    'improve' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <title><?php echo $t['title']; ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <link rel="icon" href="favicon.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --brand-color: #007BFF;
            --brand-hover: #0056b3;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: #1F2937;
        }
        .top-banner {
            background-color: var(--brand-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }
        .nav-links-container { flex: 1; overflow-x: auto; position: relative; padding: 12px 20px; }
        .nav-links { display: flex; gap: 12px; white-space: nowrap; }
        .nav-links::-webkit-scrollbar { display: none; }
        .nav-scroll-indicator { position: absolute; top: 0; left: 0; height: 3px; background: #fff; border-radius: 2px; width: 0%; transition: width 0.2s linear; }
        .nav-links a { text-decoration: none; color: #fff; font-weight: 500; padding: 8px 12px; border-radius: 4px; flex-shrink: 0; transition: background 0.3s, transform 0.15s ease; }
        .nav-links a:hover { background-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
        .lang-dropdown { position: relative; flex-shrink: 0; }
        .lang-btn-icon { width: 42px; height: 42px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,0.35); background: rgba(255,255,255,0.18); color: #fff; border-radius: 50%; cursor: pointer; transition: transform .15s ease, background .2s ease; }
        .lang-btn-icon:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
        .lang-dropdown-content { display: none; position: absolute; right: 0; top: calc(100% + 8px); background-color: #fff; min-width: 190px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden; }
        .lang-dropdown-content a { display: block; color: #333; padding: 12px 16px; text-decoration: none; transition: background .2s; white-space: nowrap; }
        .lang-dropdown-content a:hover { background: #f2f2f2; }
        .lang-dropdown-content a.active { font-weight: 700; background: #f7f7f7; }
        .right-actions { display: flex; align-items: center; gap: 10px; margin-right: 12px; }

        /* Page layout */
        .content-section {
            max-width: 780px;
            margin: 0 auto;
            padding: 30px 20px 40px;
        }
        .content-section h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--brand-color);
            text-align: center;
            margin-bottom: 8px;
        }
        .content-section p.lead {
            text-align: center;
            color: #374151;
            font-size: 1.05rem;
            margin-bottom: 28px;
        }

        /* Version card */
        .version-card {
            background: #ffffffcc;
            backdrop-filter: blur(6px);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 10px 18px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        .version-header {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 14px;
        }
        .version-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--brand-color);
        }
        .version-date {
            font-size: 0.88rem;
            color: #6B7280;
        }
        .change-list { list-style: none; padding: 0; margin: 0; }
        .change-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 7px 0;
            border-top: 1px solid rgba(0,0,0,0.05);
            font-size: 0.97rem;
            color: #374151;
            line-height: 1.5;
        }
        .change-item:first-child { border-top: none; }
        .tag {
            flex-shrink: 0;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 99px;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .footer-min {
            text-align: center;
            font-size: 0.9rem;
            color: #6B7280;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="content-section">
    <h1><?php echo $t['heading']; ?></h1>
    <p class="lead"><?php echo $t['subtitle']; ?></p>

    <?php foreach ($versions as $v): ?>
    <div class="version-card">
        <div class="version-header">
            <span class="version-number"><?php echo htmlspecialchars($v['version']); ?></span>
            <span class="version-date"><?php
                $d = DateTime::createFromFormat('Y-m-d', $v['date']);
                echo $lang === 'th'
                    ? $d->format('j') . ' ' . ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'][(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543)
                    : $d->format('F j, Y');
            ?></span>
        </div>
        <ul class="change-list">
            <?php foreach ($v['changes'] as $c):
                $color = $tagColors[$c['type']] ?? $tagColors['new'];
                $label = $t[$c['type']];
                $text  = $lang === 'th' ? $c['th'] : $c['en'];
            ?>
            <li class="change-item">
                <span class="tag" style="background:<?php echo $color['bg']; ?>;color:<?php echo $color['text']; ?>"><?php echo $label; ?></span>
                <span><?php echo htmlspecialchars($text); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>

    <div class="footer-min">
        Future X Korat &mdash; <?php echo $lang === 'th' ? 'สงวนลิขสิทธิ์' : 'All rights reserved.'; ?>
    </div>
</div>

<script>
(function () {
    var lIcon = document.getElementById('langIcon');
    var lMenu = document.getElementById('langMenu');
    if (lIcon && lMenu) {
        lIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = lMenu.style.display === 'block';
            lMenu.style.display = open ? 'none' : 'block';
            lIcon.setAttribute('aria-expanded', open ? 'false' : 'true');
        });
        document.addEventListener('click', function () { lMenu.style.display = 'none'; });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') lMenu.style.display = 'none'; });
    }
})();
</script>
</body>
</html>
