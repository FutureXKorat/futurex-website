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
        'version' => 'V25.0',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'new', 'en' => 'Admin panel: added a "Users" tool for admins to look up customer accounts and update a customer\'s username when needed. Customers now get an email whenever their username is changed by an admin.', 'th' => 'แผงแอดมิน: เพิ่มเครื่องมือ "ผู้ใช้" ให้แอดมินค้นหาบัญชีลูกค้าและแก้ไขชื่อผู้ใช้ของลูกค้าได้เมื่อจำเป็น ลูกค้าจะได้รับอีเมลแจ้งเตือนทุกครั้งที่แอดมินเปลี่ยนชื่อผู้ใช้ของพวกเขา'],
        ],
    ],
    [
        'version' => 'V24.3',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'new', 'en' => 'Settings page: added an "Edit Name" card to update your first name and surname, and a "Phone Number" card to update your phone number.', 'th' => 'หน้าการตั้งค่า: เพิ่มการ์ด "แก้ไขชื่อ" สำหรับอัปเดตชื่อและนามสกุล และการ์ด "เบอร์โทรศัพท์" สำหรับอัปเดตเบอร์โทร'],
        ],
    ],
    [
        'version' => 'V24.2',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Scroll-in animations on the home page now replay every time a section enters view, instead of only playing once and staying revealed forever.', 'th' => 'แอนิเมชันการเลื่อนเข้าหน้าหลักจะเล่นซ้ำทุกครั้งที่ส่วนนั้นเข้ามาในมุมมอง แทนที่จะเล่นเพียงครั้งเดียวแล้วค้างอยู่ตลอดไป'],
        ],
    ],
    [
        'version' => 'V24.1',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'improve', 'en' => 'PHP errors and warnings are no longer shown directly to visitors — they are logged for us to review instead, so a stray warning can no longer break a page the way it just did with Google sign-in.', 'th' => 'ข้อผิดพลาดและคำเตือนของ PHP จะไม่แสดงให้ผู้เข้าชมเห็นโดยตรงอีกต่อไป — จะถูกบันทึกไว้ให้เราตรวจสอบแทน ทำให้คำเตือนเล็กๆ น้อยๆ ไม่สามารถทำให้หน้าเว็บพังได้เหมือนที่เพิ่งเกิดขึ้นกับการเข้าสู่ระบบด้วย Google'],
        ],
    ],
    [
        'version' => 'V24.0B',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed Google sign-in breaking with a "headers already sent" error — caused by a harmless PHP deprecation notice that was leaking into the page output. Removed the outdated code that triggered it.', 'th' => 'แก้ไขปัญหาการเข้าสู่ระบบด้วย Google ที่พังพร้อมข้อผิดพลาด "headers already sent" — สาเหตุมาจากข้อความแจ้งเตือนที่ไม่เป็นอันตรายของ PHP ที่รั่วไหลเข้าไปในหน้าเว็บ ได้ลบโค้ดเก่าที่ทำให้เกิดปัญหานี้ออกแล้ว'],
        ],
    ],
    [
        'version' => 'V24.0A',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Actually fixed "Sign in with Google" this time — the bare futurexthailand.com domain (no "www") has no working HTTPS, so Google\'s callback was hanging forever. Google sign-in now uses www.futurexthailand.com, which works correctly.', 'th' => 'แก้ไขปัญหา "เข้าสู่ระบบด้วย Google" ได้จริงในครั้งนี้ — โดเมน futurexthailand.com แบบไม่มี "www" ไม่รองรับ HTTPS ทำให้การตอบกลับจาก Google ค้างตลอดไป ตอนนี้ระบบเข้าสู่ระบบด้วย Google เปลี่ยนไปใช้ www.futurexthailand.com ซึ่งทำงานได้อย่างถูกต้อง'],
        ],
    ],
    [
        'version' => 'V24.0',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed "Sign in with Google" timing out every time — the server was stalling on a broken IPv6 route to Google\'s servers before falling back to a working connection. It now connects directly over IPv4.', 'th' => 'แก้ไขปัญหา "เข้าสู่ระบบด้วย Google" ค้างจนหมดเวลาทุกครั้ง — เดิมเซิร์ฟเวอร์ค้างอยู่ที่เส้นทาง IPv6 ที่เชื่อมต่อไปยังเซิร์ฟเวอร์ของ Google ไม่ได้ ก่อนจะย้อนกลับไปใช้การเชื่อมต่อที่ใช้งานได้ ตอนนี้จะเชื่อมต่อผ่าน IPv4 โดยตรงแทน'],
        ],
    ],
    [
        'version' => 'V23.0',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'improve', 'en' => 'Redesigned the profile picture cropping tool with a cleaner popup, and the picture itself can no longer be dragged around — only the circular crop area moves and resizes.', 'th' => 'ออกแบบเครื่องมือครอบตัดรูปโปรไฟล์ใหม่ให้ดูทันสมัยขึ้น และรูปภาพจะไม่สามารถลากย้ายได้อีกต่อไป — เลื่อนและปรับขนาดได้เฉพาะกรอบครอบตัดวงกลมเท่านั้น'],
            ['type' => 'improve', 'en' => 'Zooming while cropping your profile picture now feels the same whether you use a mouse wheel, a touchpad, or touch, with new +/- buttons and a slider for easy control.', 'th' => 'การซูมขณะครอบตัดรูปโปรไฟล์ทำงานเหมือนกัน ไม่ว่าจะใช้ล้อเลื่อนเมาส์ ทัชแพด หรือหน้าจอสัมผัส พร้อมปุ่ม +/- และแถบเลื่อนใหม่ให้ควบคุมง่ายขึ้น'],
            ['type' => 'improve', 'en' => '"Delete Profile Picture" button now matches the site\'s modern gradient button style instead of the old plain red box.', 'th' => 'ปุ่ม "ลบรูปโปรไฟล์" ใช้สไตล์ปุ่มไล่สีทันสมัยแบบเดียวกับเว็บไซต์ แทนกล่องสีแดงธรรมดาแบบเดิม'],
        ],
    ],
    [
        'version' => 'V22.7',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'improve', 'en' => 'Extended the "stay where you were" scroll fix to the Stock, Products, Settings, and Users admin pages, not just the Order Log.', 'th' => 'ขยายการแก้ไขให้คงตำแหน่งการเลื่อนหน้าจอไปยังหน้าสต็อก สินค้า ตั้งค่า และผู้ใช้งานของแอดมิน ไม่ใช่แค่หน้าบันทึกคำสั่งซื้อ'],
        ],
    ],
    [
        'version' => 'V22.6A',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed the scroll-position fix from V22.6 not working on phones/tablets — it relied on an event that iOS Safari does not reliably fire, so it now saves your scroll position continuously instead.', 'th' => 'แก้ไขปัญหาการคงตำแหน่งการเลื่อนหน้าจอจาก V22.6 ที่ไม่ทำงานบนมือถือ/แท็บเล็ต — เดิมอาศัยอีเวนต์ที่ iOS Safari ไม่ยิงอย่างสม่ำเสมอ ตอนนี้จะบันทึกตำแหน่งการเลื่อนอย่างต่อเนื่องแทน'],
        ],
    ],
    [
        'version' => 'V22.6',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'improve', 'en' => 'The admin Order Log page now stays at the same scroll position after approving, rejecting, completing, reverting, or deleting an order, instead of jumping back to the top.', 'th' => 'หน้าบันทึกคำสั่งซื้อของแอดมินจะคงตำแหน่งการเลื่อนหน้าจอเดิมไว้หลังจากอนุมัติ ปฏิเสธ ทำเครื่องหมายเสร็จสิ้น คืนสถานะ หรือลบคำสั่งซื้อ แทนที่จะเลื่อนกลับไปด้านบนสุด'],
        ],
    ],
    [
        'version' => 'V22.5A',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'The customer-facing "My Orders" page now shows completed (Picked Up / Shipped) orders correctly instead of showing them as Pending Review.', 'th' => 'หน้า "คำสั่งซื้อของฉัน" ของลูกค้าแสดงสถานะคำสั่งซื้อที่เสร็จสิ้น (รับสินค้าแล้ว / จัดส่งแล้ว) ถูกต้อง แทนที่จะแสดงเป็นรอตรวจสอบ'],
            ['type' => 'fix', 'en' => 'Fixed the same stock-not-restored bug on the customer side — deleting your own approved or completed order now restores stock too.', 'th' => 'แก้ไขปัญหาสต็อกไม่คืนกลับแบบเดียวกันในฝั่งลูกค้า — การลบคำสั่งซื้อที่อนุมัติหรือเสร็จสิ้นแล้วของคุณเองจะคืนสต็อกกลับให้ด้วยเช่นกัน'],
        ],
    ],
    [
        'version' => 'V22.5',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Deleting an approved or completed order now restores its stock — previously the stock stayed deducted forever with no order left to explain it.', 'th' => 'การลบคำสั่งซื้อที่อนุมัติหรือเสร็จสิ้นแล้ว จะคืนสต็อกสินค้ากลับให้อัตโนมัติ — เดิมสต็อกจะถูกตัดค้างไว้ตลอดไปโดยไม่มีคำสั่งซื้อเหลืออยู่ให้ตรวจสอบ'],
            ['type' => 'new', 'en' => 'Added a "Picked Up" / "Shipped" button on approved orders to mark them as completed, with a new Completed status, stat card, and filter tab.', 'th' => 'เพิ่มปุ่ม "รับสินค้าแล้ว" / "จัดส่งแล้ว" สำหรับคำสั่งซื้อที่อนุมัติแล้ว พร้อมสถานะ "เสร็จสิ้น" การ์ดสรุป และแท็บกรองใหม่'],
        ],
    ],
    [
        'version' => 'V22.4B',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed the navbar swipe hint sitting short of the edge, which let menu text peek out uncovered next to it on phones/tablets.', 'th' => 'แก้ไขคำแนะนำการปัดในแถบเมนูที่อยู่ไม่ถึงขอบจอ ทำให้ข้อความเมนูโผล่ออกมาข้างๆ โดยไม่ถูกบัง'],
        ],
    ],
    [
        'version' => 'V22.4A',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed the new navbar swipe hint drifting out of place and cutting through menu text while scrolling on phones/tablets.', 'th' => 'แก้ไขคำแนะนำการปัดในแถบเมนูที่เลื่อนหลุดตำแหน่งและตัดผ่านข้อความเมนูขณะเลื่อนบนมือถือ/แท็บเล็ต'],
        ],
    ],
    [
        'version' => 'V22.4',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'improve', 'en' => 'Added a swipe hint on the navigation bar for phones and tablets, so it\'s clear there are more menu items when the navbar doesn\'t fit the screen.', 'th' => 'เพิ่มคำแนะนำการปัดในแถบเมนูสำหรับมือถือและแท็บเล็ต เพื่อให้เห็นชัดว่ามีรายการเมนูเพิ่มเติมเมื่อแถบเมนูแสดงไม่พอดีหน้าจอ'],
        ],
    ],
    [
        'version' => 'V22.3A',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'fix', 'en' => 'The Cancel button in the new confirmation popups now matches the same corner roundness as the button next to it.', 'th' => 'ปุ่มยกเลิกในป๊อปอัปยืนยันแบบใหม่มีความโค้งมนของมุมตรงกับปุ่มข้างๆ แล้ว'],
        ],
    ],
    [
        'version' => 'V22.3',
        'date'    => '2026-07-02',
        'changes' => [
            ['type' => 'improve', 'en' => 'Deleting an order now shows a styled on-site popup instead of the browser\'s plain confirm box.', 'th' => 'การลบคำสั่งซื้อแสดงป๊อปอัปสไตล์ของเว็บไซต์แทนกล่องยืนยันแบบเดิมของเบราว์เซอร์'],
            ['type' => 'improve', 'en' => 'Admin panel: delete, revert-to-pending, and bulk approve/reject confirmations now use styled on-site popups instead of the browser\'s plain popups.', 'th' => 'หน้าแอดมิน: การยืนยันลบ, คืนสถานะเป็นรอตรวจสอบ, และอนุมัติ/ปฏิเสธแบบกลุ่ม ใช้ป๊อปอัปสไตล์ของเว็บไซต์แทนป๊อปอัปเดิมของเบราว์เซอร์'],
            ['type' => 'new', 'en' => 'Admin panel: bulk-rejecting orders now lets you pick a rejection reason, same as rejecting a single order.', 'th' => 'หน้าแอดมิน: การปฏิเสธคำสั่งซื้อแบบกลุ่มสามารถเลือกเหตุผลการปฏิเสธได้แล้ว เหมือนกับการปฏิเสธทีละรายการ'],
        ],
    ],
    [
        'version' => 'V22.2B',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'fix', 'en' => 'Home page trust row now correctly shows "2 Countries — Manufacturing in Malaysia, Distributed in Thailand".', 'th' => 'แถบข้อมูลในหน้าแรกแสดงผล "2 ประเทศ — ผลิตในมาเลเซีย จัดจำหน่ายในประเทศไทย" อย่างถูกต้อง'],
            ['type' => 'fix', 'en' => 'Fixed uneven spacing in the About page credits between ChatGPT and Claude.', 'th' => 'แก้ไขระยะห่างที่ไม่เท่ากันระหว่าง ChatGPT และ Claude ในหน้าเครดิต'],
        ],
    ],
    [
        'version' => 'V22.2A',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'fix', 'en' => 'Fixed the home page scroll arrow blending into the wave graphic on wide screens.', 'th' => 'แก้ไขลูกศรเลื่อนหน้าในหน้าแรกที่กลืนไปกับเส้นคลื่นบนหน้าจอกว้าง'],
            ['type' => 'fix', 'en' => 'Corrected warehouse and manufacturing facts across the site: 20 warehouses across Thailand, manufacturing only in Malaysia (not Indonesia).', 'th' => 'แก้ไขข้อมูลคลังสินค้าและการผลิตทั่วทั้งเว็บไซต์: มีคลังสินค้า 20 แห่งทั่วไทย ผลิตที่มาเลเซียเท่านั้น (ไม่มีอินโดนีเซีย)'],
            ['type' => 'new', 'en' => 'Added Claude to the website programmer credits on the About page.', 'th' => 'เพิ่ม Claude ในเครดิตโปรแกรมเมอร์เว็บไซต์ที่หน้าเกี่ยวกับเรา'],
        ],
    ],
    [
        'version' => 'V22.2',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'new', 'en' => 'Home page hero now has a tagline, a smoother wave transition into the page, and a new "17 Warehouses / 2 Countries / High Quality" trust row.', 'th' => 'หน้าแรกมีข้อความแท็กไลน์ใหม่ เส้นคลื่นเชื่อมส่วนต่างๆ ที่นุ่มนวลขึ้น และแถบข้อมูล "17 คลังสินค้า / 2 ประเทศ / คุณภาพสูง"'],
            ['type' => 'improve', 'en' => 'Home page feature carousel now has softer shadows, icon backgrounds, brand-colored dots, and fades in on scroll like the rest of the page.', 'th' => 'สไลด์แนะนำโบนัสในหน้าแรกมีเงาที่นุ่มนวลขึ้น พื้นหลังไอคอน จุดสีตามแบรนด์ และค่อยๆ ปรากฏขึ้นเมื่อเลื่อนหน้าจอเหมือนส่วนอื่น'],
        ],
    ],
    [
        'version' => 'V22.1B',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'fix', 'en' => 'Version Log page title now sits at the same height as About Us and Sources — was missing the same 20px spacer below the navbar.', 'th' => 'หัวข้อในหน้าประวัติการอัปเดตอยู่ในระดับความสูงเดียวกับหน้าเกี่ยวกับเราและแหล่งที่มาแล้ว — ก่อนหน้านี้ขาดระยะห่าง 20px ใต้แถบนำทาง'],
            ['type' => 'fix', 'en' => 'Fixed changelog ordering — lettered patches (e.g. V22.1A / V22.1B) now list top to bottom as B, then A, matching every other lettered version on this page.', 'th' => 'แก้ไขลำดับบันทึกการอัปเดต — แพตช์ที่มีตัวอักษรต่อท้าย (เช่น V22.1A / V22.1B) เรียงจากบนลงล่างเป็น B แล้วตามด้วย A ให้ตรงกับเวอร์ชันอื่นๆ ในหน้านี้'],
        ],
    ],
    [
        'version' => 'V22.1A',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'fix', 'en' => 'Version Log page title now truly matches About Us and Sources — a leftover font-weight and bottom padding mismatch from V22.1 is now resolved.', 'th' => 'หัวข้อในหน้าประวัติการอัปเดตตรงกับหน้าเกี่ยวกับเราและแหล่งที่มาแล้วจริงๆ — แก้ไขปัญหาน้ำหนักตัวอักษรและ padding ด้านล่างที่ตกค้างจาก V22.1'],
            ['type' => 'improve', 'en' => 'About Us cards now have equal spacing above the title and below the content, consistent across every card.', 'th' => 'การ์ดในหน้าเกี่ยวกับเรามีระยะห่างเหนือหัวข้อและใต้เนื้อหาเท่ากันในทุกการ์ดแล้ว'],
        ],
    ],
    [
        'version' => 'V22.1',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'improve', 'en' => 'Version Log page title now matches the font size used on About Us and Sources.', 'th' => 'หัวข้อในหน้าประวัติการอัปเดตใช้ขนาดตัวอักษรเดียวกับหน้าเกี่ยวกับเราและแหล่งที่มาแล้ว'],
            ['type' => 'improve', 'en' => 'About Us and Sources pages now use separate cards per section, letting the background gradient show through — less gloomy, more consistent with the Version Log page.', 'th' => 'หน้าเกี่ยวกับเราและแหล่งที่มาแบ่งเป็นการ์ดแยกแต่ละหัวข้อ ทำให้เห็นพื้นหลังไล่สีชัดขึ้น ดูสว่างและเข้ากับหน้าประวัติการอัปเดตมากขึ้น'],
            ['type' => 'improve', 'en' => 'The version number on the home page footer now fades in with the same scroll animation as the rest of the page.', 'th' => 'หมายเลขเวอร์ชันที่ท้ายหน้าแรกจะค่อยๆ ปรากฏขึ้นด้วยอนิเมชันเดียวกับส่วนอื่นของหน้า'],
        ],
    ],
    [
        'version' => 'V22.0',
        'date'    => '2026-07-01',
        'changes' => [
            ['type' => 'new', 'en' => 'A red badge on the cart icon now shows how many items are in your cart, visible from every page except the cart itself.', 'th' => 'ไอคอนตะกร้าสินค้ามีจุดแดงแสดงจำนวนสินค้าที่อยู่ในตะกร้า มองเห็นได้จากทุกหน้า ยกเว้นหน้าตะกร้าเอง'],
        ],
    ],
    [
        'version' => 'V21.3D',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'new',     'en' => 'Admin Settings page: Change Password form now shows the same live password requirements checklist as the user-facing settings page.', 'th' => 'หน้าการตั้งค่าแอดมิน: แบบฟอร์มเปลี่ยนรหัสผ่านแสดงรายการข้อกำหนดรหัสผ่านแบบเรียลไทม์เหมือนกับหน้าการตั้งค่าของผู้ใช้แล้ว'],
            ['type' => 'improve', 'en' => 'Admin Settings page: password rules now match all other forms — 6–12 characters, number, lowercase letter, capital letter.', 'th' => 'หน้าการตั้งค่าแอดมิน: กฎรหัสผ่านตอนนี้ตรงกับแบบฟอร์มอื่นทั้งหมด — 6–12 ตัวอักษร มีตัวเลข ตัวพิมพ์เล็ก และตัวพิมพ์ใหญ่'],
        ],
    ],
    [
        'version' => 'V21.4',
        'date'    => '2026-06-30',
        'changes' => [
            ['type' => 'fix', 'en' => 'Language button on the Version Log page now works — a duplicate click listener was cancelling the menu open immediately after it opened.', 'th' => 'ปุ่มเปลี่ยนภาษาในหน้าประวัติการอัปเดตใช้งานได้แล้ว — ลบ event listener ซ้ำซ้อนที่ทำให้เมนูปิดทันทีหลังจากเปิด'],
        ],
    ],
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
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
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
            padding: 30px 20px 30px 20px;
        }
        .content-section h1 {
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--brand-color);
            text-align: center;
            margin-bottom: 10px;
        }
        .content-section p.lead {
            text-align: center;
            color: #374151;
            font-size: 1.05rem;
            margin-bottom: 20px;
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
        .spacer-nav { height: 20px; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="spacer-nav"></div>

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

</body>
</html>
