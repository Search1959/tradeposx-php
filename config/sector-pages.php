<?php
/**
 * Public landing page per sector: /pos/{slug} (TradeERP) and /software/{slug} (ServiceERP).
 *
 * Built in phases of 15. A sector's page is live only when its phase <= 'live_phase'.
 * To ship the next phase: add 'content' for that phase's sectors, then raise 'live_phase'.
 *
 * Rules for the copy (it is public marketing, so it must stay true):
 *  - only describe things the app really does (see the feature list in the landing page / memory);
 *  - no invented numbers, customers or testimonials; GST is always "confirm with your accountant";
 *  - say plainly what it does NOT do where a visitor would assume otherwise.
 *
 * The same structure is meant to feed a printable per-sector leaflet later.
 */
return [
    'live_phase' => 1,
    'updated'    => '2026-11-08',

    // Which sectors belong to which phase, in the order they will be written.
    'phases' => [
        1 => ['KIRANA_FMCG', 'PHARMACY', 'BUILDING_HARDWARE', 'MOBILE_COMPUTERS', 'BAKERY', 'SWEETS_MITHAI', 'ELECTRICAL_ELECTRONICS',
              'SALON', 'DOCTOR_CLINIC', 'GYM', 'REPAIR_SHOP', 'PRINTING_PRESS', 'PHOTOGRAPHY', 'RESTAURANT_CAFE', 'AUTOMOBILE_GARAGE'],
        2 => ['GENERAL_TRADING', 'FOOTWEAR_GARMENTS', 'COSMETICS', 'STATIONERY', 'DAIRY_BEVERAGE', 'FRUITS_VEGETABLES', 'TEXTILES',
              'DENTAL_CLINIC', 'DIAGNOSTIC_CENTRE', 'SPA_WELLNESS', 'MOBILE_LAPTOP_REPAIR', 'HOME_APPLIANCE_REPAIR', 'AC_SERVICE', 'CAR_WASH', 'TAILORING_BOUTIQUE'],
        3 => ['JEWELLERY', 'FURNITURE_WOOD', 'AUTO_PARTS', 'AGRICULTURE', 'WATER_RO', 'PLASTICS_PACKAGING',
              'TWO_WHEELER_SERVICE', 'COACHING_CENTRE', 'TUITION', 'YOGA_CENTRE', 'ELECTRICIAN', 'PLUMBING', 'LAUNDRY', 'EVENT_MANAGEMENT', 'HOTEL_GUESTHOUSE'],
        4 => ['METALS_STEEL', 'BALL_BEARING', 'CHEMICALS', 'ENERGY', 'SEEDS_FERTILIZERS',
              'HEALTHCARE_CLINIC', 'HOSPITAL', 'PHYSIOTHERAPY', 'AYURVEDA_HOMEOPATHY', 'VETERINARY_CLINIC', 'PET_GROOMING', 'PEST_CONTROL', 'HOME_CLEANING', 'CATERING', 'CCTV_SECURITY_INSTALL'],
        5 => ['LAWYER', 'CA_ACCOUNTANT', 'CONSULTANT', 'SOFTWARE_IT', 'DIGITAL_MARKETING_AGENCY', 'ADVERTISING_CREATIVE', 'COMPUTER_AMC', 'REAL_ESTATE',
              'TRAVEL_AGENCY', 'CONSTRUCTION', 'INTERIOR_DESIGN', 'ARCHITECTURE', 'SOLAR_INSTALLATION', 'PAINTING_WATERPROOFING', 'CARPENTRY_FURNITURE'],
        6 => ['RO_WATER_PURIFIER', 'AUTO_CARE_DECOR', 'DRIVING_SCHOOL', 'PLAY_SCHOOL_DAYCARE', 'CYBER_CAFE_XEROX', 'PACKERS_MOVERS', 'TAXI_CAR_RENTAL', 'COURIER_LOGISTICS',
              'WELDING_FABRICATION', 'GARDENING_LANDSCAPING', 'SECURITY_AGENCY', 'FINANCE_LOANS', 'GOVT_CSC', 'AGRICULTURE_SERVICE', 'ENTERTAINMENT_SPORTS'],
        7 => ['RELIGIOUS_TRUST', 'GENERAL_SERVICE'],
    ],

    // Per sector: slug (URL), noun (as used in sentences), title (<title>, brand is added), d (meta description),
    // headline, sub, pains [3 x [title, text]], features [6 x [title, text]], faqs [3 x [question, answer]].
    'content' => [

        // ───────────────────────── TradeERP ─────────────────────────
        'KIRANA_FMCG' => [
            'slug' => 'kirana-store', 'noun' => 'Kirana & Grocery Store',
            'title' => 'Kirana Store POS & Billing Software',
            'd' => 'Bill kirana and grocery items in seconds, track stock and Udhaar, and send bills on WhatsApp. Works on your phone. Try the live demo.',
            'headline' => 'Billing software for kirana & grocery stores',
            'sub' => 'Bill at the counter in seconds, know what is running out, and stop chasing Udhaar in a notebook. All from your phone, with no POS machine.',
            'pains' => [
                ['Queues at the counter', 'Tap items or scan barcodes and finish the bill in seconds. Change the price on the spot when a regular customer bargains.'],
                ['Udhaar in a notebook', 'Every customer’s credit balance, part payments and statement in one place, with a WhatsApp reminder when dues build up.'],
                ['Finding out an item is finished too late', 'Low-stock alerts and purchase bills keep your shelves and your supplier list under control.'],
            ],
            'features' => [
                ['Fast tap-to-bill POS', 'Colour-coded product cards, search or barcode scan, discounts, and paid, part-paid or credit sales.'],
                ['Loose and packed items', 'Sell by kg, gram, litre or piece. Speak or type a line like “atta 2 kg” into the cash memo.'],
                ['GST bills when you need them', 'Exclusive, inclusive or exempt, with a rate per item and the CGST/SGST split.'],
                ['Udhaar ledger', 'Dues by customer, statements, and one-tap WhatsApp reminders.'],
                ['Bill on WhatsApp', 'Send every bill as a PDF or image straight from the sale screen.'],
                ['Works on your phone', 'Install it on your home screen. Quick Sale keeps working when the internet drops.'],
            ],
            'faqs' => [
                ['Can I bill items by weight?', 'Yes. Each item has a unit, and the cash memo accepts kg, gram, litre and other units, so you can bill 250 g of an item priced per kg.'],
                ['Does it handle GST?', 'Yes. Choose exclusive, inclusive or exempt on each bill, with a rate per item. Please confirm your rates with your accountant.'],
                ['Can I bring in my existing item list?', 'Yes. Import a CSV, or photograph a printed price list and review what the app reads before anything is saved. A ready-made kirana starter list is available inside the app.'],
            ],
        ],

        'PHARMACY' => [
            'slug' => 'pharmacy-medical-store', 'noun' => 'Pharmacy & Medical Store',
            'title' => 'Pharmacy & Medical Store Billing Software',
            'd' => 'Fast counter billing for pharmacies with batch and expiry details, HSN and GST, credit for regular customers and low-stock alerts. Try the live demo.',
            'headline' => 'Billing software for pharmacies & medical stores',
            'sub' => 'Fast counter billing with batch and expiry details on every product, credit for regular customers, and stock you can trust.',
            'pains' => [
                ['Batch and expiry details', 'Record the batch number, manufacturing date and expiry date on each product so they are always at hand.'],
                ['Regular customers on credit', 'Keep a running balance for regular customers and send a WhatsApp reminder when payment is due.'],
                ['A large item list to set up', 'Import your product list from a CSV, or photograph a supplier bill and review the items before saving.'],
            ],
            'features' => [
                ['Quick counter billing', 'Search by name, SKU or barcode, apply a discount, and take cash, UPI or credit.'],
                ['HSN and GST per product', 'Store an HSN code and GST rate on each product; bills can be exclusive, inclusive or exempt.'],
                ['Purchases and suppliers', 'Enter purchase bills, keep supplier details and see stock rise as you buy.'],
                ['Low-stock alerts', 'See at a glance which products are at or below their minimum stock.'],
                ['Udhaar ledger', 'Statements, part payments and WhatsApp reminders for customers who pay later.'],
                ['Reports and profit', 'Daily sales, top items and real margin from your own purchase and sale prices.'],
            ],
            'faqs' => [
                ['Can I record batch number and expiry date?', 'Yes. Every product has fields for batch number, manufacturing date and expiry date.'],
                ['Does it handle GST and HSN?', 'Yes. Add an HSN code and a GST rate per product. Medicines can fall under different rates, so please confirm yours with your accountant.'],
                ['Does it replace the records my licence requires?', 'No. It handles billing, stock and customer dues. Keep any registers your licence requires.'],
            ],
        ],

        'BUILDING_HARDWARE' => [
            'slug' => 'hardware-building-materials', 'noun' => 'Hardware & Building Materials Shop',
            'title' => 'Hardware & Building Materials Billing Software',
            'd' => 'Bill cement, pipes, paint and fittings fast, track contractor credit and broker commission, and keep GST and stock in order. Try the live demo.',
            'headline' => 'Billing software for hardware & building material shops',
            'sub' => 'Bill cement, pipes, paint and fittings quickly, keep contractor credit under control, and keep broker commission in the books.',
            'pains' => [
                ['Contractors buying on credit', 'An Udhaar ledger with part payments, statements and WhatsApp reminders for each contractor.'],
                ['Thousands of small items', 'Search by name, SKU or barcode, import the whole list from a CSV, or photograph a price list.'],
                ['Broker commission to track', 'Attach a broker to a sale and keep the commission payable on record.'],
            ],
            'features' => [
                ['Tap-to-bill POS', 'Find an item by name or code, set quantity and rate, and finish the bill in a few taps.'],
                ['GST with HSN', 'Exclusive, inclusive or exempt, with a GST rate and HSN code per item.'],
                ['Purchases and suppliers', 'Enter purchase bills and watch stock update as material arrives.'],
                ['Udhaar ledger', 'Credit balances by customer, with statements and WhatsApp reminders.'],
                ['Broker commission', 'Track what is owed to each broker on the sales they bring.'],
                ['Reports', 'Daily sales, top items, expenses and profit from your own data.'],
            ],
            'faqs' => [
                ['Can I bill in bags, kg, feet or pieces?', 'Yes. Each item has its own unit, so cement can be sold by the bag and wire by the metre or piece.'],
                ['Can my staff use it too?', 'Yes. Add staff with their own logins. Owners and admins see everything; an activity log shows who changed what.'],
                ['Does it track broker commission?', 'Yes. You can attach a broker to a sale and see the commission you owe.'],
            ],
        ],

        'MOBILE_COMPUTERS' => [
            'slug' => 'mobile-computer-shop', 'noun' => 'Mobile & Computer Shop',
            'title' => 'Mobile & Computer Shop Billing Software',
            'd' => 'Bill phones, laptops and accessories with barcode scan, track stock by item, and add repair job cards in the same login. Try the live demo.',
            'headline' => 'Billing software for mobile & computer shops',
            'sub' => 'Bill phones, laptops and accessories with barcode scan, keep stock by item, and add repairs when you need them.',
            'pains' => [
                ['Hundreds of accessories', 'Scan or search by name, SKU or barcode, with low-stock alerts for chargers, cases and cables.'],
                ['Walk-in repair jobs', 'Use ServiceERP in the same login for job cards, parts and warranty days.'],
                ['Customers paying in parts', 'Part payments and credit, with dues reminders on WhatsApp.'],
            ],
            'features' => [
                ['Barcode-ready POS', 'Scan a code or search by name and finish the bill in a few taps.'],
                ['Stock by item', 'Opening stock, purchases, sales and low-stock alerts for every item.'],
                ['Repairs in the same login', 'Run TradeERP for sales and ServiceERP for job cards and repair bills together.'],
                ['GST invoices', 'Exclusive, inclusive or exempt, with a rate per item.'],
                ['Bill on WhatsApp', 'Send the bill as a PDF or image to the customer.'],
                ['Reports and profit', 'Sales, top sellers, expenses and margin from your own numbers.'],
            ],
            'faqs' => [
                ['Can I sell phones and run repairs together?', 'Yes. A business can use TradeERP and ServiceERP under one login.'],
                ['Can I import my stock list?', 'Yes. Import a CSV or photograph a printed list and check it before saving.'],
                ['Does it work without internet?', 'The Quick Sale phone screen keeps working offline and syncs your sales when you are back online.'],
            ],
        ],

        'BAKERY' => [
            'slug' => 'bakery', 'noun' => 'Bakery',
            'title' => 'Bakery POS & Billing Software',
            'd' => 'Tap-to-bill for breads, cakes and snacks, advance payments on custom orders and daily sales at a glance. Works on your phone. Try the live demo.',
            'headline' => 'Billing software for bakeries',
            'sub' => 'Tap-to-bill for breads, cakes and snacks, advance payments on custom orders, and daily sales you can see at a glance.',
            'pains' => [
                ['The rush-hour counter', 'Colour-coded product cards make billing a tap away, even one-handed on a phone.'],
                ['Custom cake orders', 'Take an advance as a part payment and collect the balance on delivery, with a WhatsApp reminder.'],
                ['Stock you cannot see', 'Track packed items and ingredients, with low-stock alerts.'],
            ],
            'features' => [
                ['Quick Sale on your phone', 'A fast card-tap screen for the counter, installable on your home screen.'],
                ['Loose and packed items', 'Bill by piece, kg or pack. Speak or type a line into the cash memo.'],
                ['Advance and balance', 'Part payments with the balance shown on the bill.'],
                ['UPI at the counter', 'Show a UPI QR with the bill amount at checkout.'],
                ['Bill on WhatsApp', 'Send bills and order slips as a PDF or image.'],
                ['Reports', 'Daily sales, top items and expenses.'],
            ],
            'faqs' => [
                ['Can I take advance payments on cake orders?', 'Yes. Record a part payment now and the balance is shown as due until you collect it.'],
                ['Can I bill by weight?', 'Yes. Items can use kg or gram units, so a cake sold per kg can be billed by its weight.'],
                ['Is there a starter item list?', 'Yes. A ready-made bakery list is available inside the app, and you can import your own CSV.'],
            ],
        ],

        'SWEETS_MITHAI' => [
            'slug' => 'sweet-shop-mithai', 'noun' => 'Sweet Shop & Mithai Counter',
            'title' => 'Sweet Shop & Mithai Billing Software',
            'd' => 'Bill sweets by the kg or piece, handle gift boxes and hot snacks, share bills on WhatsApp and collect Google reviews. Try the live demo.',
            'headline' => 'Billing software for sweet shops & mithai counters',
            'sub' => 'Bill by the kg or by the piece, handle gift boxes and hot snacks, and invite happy customers to leave a Google review.',
            'pains' => [
                ['Kg, piece and plate in one bill', 'Sell loose sweets by weight and snacks by piece or plate on the same bill.'],
                ['The festival rush', 'Fast tap billing, Quick Sale on a phone, and WhatsApp bills when the counter is crowded.'],
                ['Few online reviews', 'A counter QR helps customers post genuine Google reviews in their own words.'],
            ],
            'features' => [
                ['Ready-made starter list', 'A 69-item sample list of Bengali sweets, dry sweets, namkeen, snacks and gift boxes you can import and edit.'],
                ['Weight and piece billing', 'Items in kg, pcs, box or plate, with speak-or-type cash memo entry.'],
                ['GST or Bill of Supply', 'Choose exclusive, inclusive or exempt on each bill. Please confirm your rates with your accountant.'],
                ['Google Reviews QR', 'A printable counter stand and stickers; customers scan, edit a suggested review and post it themselves.'],
                ['Bill on WhatsApp', 'Send each bill as a PDF or image.'],
                ['Works on your phone', 'Install it on your home screen for counter billing.'],
            ],
            'faqs' => [
                ['Is there a starter list of sweets?', 'Yes. A sample list of 69 items is available to import. Its prices, stock and GST rates are placeholders you should replace with your own.'],
                ['Can I sell loose sweets by weight?', 'Yes. Items can be priced per kg and billed in kg or gram.'],
                ['Does the review QR give customers anything in return?', 'No. Google does not allow rewards for reviews, so ReviewDesk does not offer any. The customer writes and posts their own review.'],
            ],
        ],

        'ELECTRICAL_ELECTRONICS' => [
            'slug' => 'electrical-electronics-shop', 'noun' => 'Electrical & Electronics Shop',
            'title' => 'Electrical & Electronics Shop Billing Software',
            'd' => 'Bill wires, switches, lights and appliances with GST and HSN, scan barcodes and keep electricians’ credit under control. Try the live demo.',
            'headline' => 'Billing software for electrical & electronics shops',
            'sub' => 'Bill wires, switches, lights and appliances with GST and HSN, scan barcodes, and keep electricians’ credit under control.',
            'pains' => [
                ['Brand and size variety', 'Search by name, SKU or barcode across wires, switches, MCBs and fixtures.'],
                ['Electricians buying on credit', 'An Udhaar ledger with part payments and WhatsApp reminders.'],
                ['GST on every bill', 'A GST rate and HSN code per item; exclusive, inclusive or exempt bills.'],
            ],
            'features' => [
                ['Fast tap-to-bill POS', 'Search or scan, edit the price when a trade customer bargains, and bill.'],
                ['Stock and low-stock alerts', 'Know which items are running low before a customer asks.'],
                ['Purchases and suppliers', 'Enter supplier bills and keep purchase prices for real margin.'],
                ['Udhaar ledger', 'Credit balances and statements by customer.'],
                ['Bill on WhatsApp', 'Send the bill as a PDF or image.'],
                ['Import your item list', 'CSV import, or photograph a printed price list and review it before saving.'],
            ],
            'faqs' => [
                ['Can I keep different prices for the same item?', 'You can change the price on any bill without changing the item’s own price, which suits trade customers who bargain.'],
                ['Does it handle GST and HSN?', 'Yes, per item. Please confirm the rates for your products with your accountant.'],
                ['Can I import hundreds of items?', 'Yes. Use the CSV import, or scan a price list and review the items first.'],
            ],
        ],

        // ───────────────────────── ServiceERP ─────────────────────────
        'SALON' => [
            'slug' => 'salon', 'noun' => 'Salon',
            'title' => 'Salon Billing & Appointment Software',
            'd' => 'Book appointments, bill salon services in a few taps and track each stylist’s commission. Works on your phone. Try the live demo.',
            'headline' => 'Billing & appointment software for salons',
            'sub' => 'Book appointments, bill services in a few taps, and track each stylist’s commission.',
            'pains' => [
                ['Missed or double-booked appointments', 'An appointment list with status keeps the day’s bookings in one place.'],
                ['Staff commission by hand', 'Assign staff to services and see the commission payable and paid out.'],
                ['Typing the whole service menu', 'Import a ready-made salon service list or photograph your printed rate card.'],
            ],
            'features' => [
                ['Service billing', 'Tap a service, add it to the bill, take cash, UPI or part payment.'],
                ['Appointments', 'Book customers and move each booking through its status.'],
                ['Staff and commission', 'Assign a stylist to each service and keep commission on record.'],
                ['Dues reminders', 'Part payments with the balance shown, and a WhatsApp reminder for dues.'],
                ['Optional GST', 'Bill with GST or without it, as your business requires. Please confirm with your accountant.'],
                ['Google Reviews QR', 'A counter QR helps happy customers post a genuine review on Google.'],
            ],
            'faqs' => [
                ['Can I take an advance for bridal or package services?', 'Yes. Record a part payment and the balance stays due on the bill until it is collected.'],
                ['Do WhatsApp reminders go out automatically?', 'No. The WhatsApp buttons open a pre-filled message on your own phone, and you tap send.'],
                ['Can stylists have their own logins?', 'Yes. Add staff accounts; an activity log shows who changed what.'],
            ],
        ],

        'DOCTOR_CLINIC' => [
            'slug' => 'doctor-clinic', 'noun' => 'Doctor Clinic',
            'title' => 'Doctor Clinic Billing & Appointment Software',
            'd' => 'Keep clinic appointments and fee billing simple: a fee list, quick receipts, part payments and a clear day’s collection. Try the live demo.',
            'headline' => 'Billing & appointment software for doctor clinics',
            'sub' => 'Keep appointments and fee billing simple: a fee list, quick receipts, part payments, and a clear day’s collection.',
            'pains' => [
                ['Receipts take too long', 'Pick the consultation or procedure from your fee list and print or share the bill.'],
                ['Appointments on paper', 'An appointment list with status for the day.'],
                ['Pending fees', 'Part payments and balance-due reminders on WhatsApp.'],
            ],
            'features' => [
                ['Fee list as service items', 'Consultation, follow-up and procedure fees ready to tap onto a bill.'],
                ['Appointments', 'Book patients and track each booking’s status.'],
                ['Quick receipts', 'Share the bill as a PDF or image on WhatsApp.'],
                ['Part payments', 'Balance shown on the bill until it is collected.'],
                ['With or without GST', 'Bill with GST or choose No GST (Bill of Supply) when a bill should carry no tax.'],
                ['Reports', 'Collections and daily totals from your own data.'],
            ],
            'faqs' => [
                ['Does it keep patient medical records?', 'No. It handles appointments, fee billing and dues. It is not an electronic medical record system.'],
                ['What about GST on medical services?', 'Many clinical services are GST-exempt. Choose No GST (Bill of Supply) when a bill should carry no tax, and confirm with your accountant.'],
                ['Can I import my fee list?', 'Yes. Import a CSV, or start from the ready-made clinic list inside the app.'],
            ],
        ],

        'GYM' => [
            'slug' => 'gym', 'noun' => 'Gym & Fitness Centre',
            'title' => 'Gym Billing & Dues Software',
            'd' => 'Bill monthly, quarterly and yearly gym plans, keep part payments and dues in view and track trainer commission. Try the live demo.',
            'headline' => 'Billing software for gyms',
            'sub' => 'Bill monthly, quarterly and yearly plans, keep part payments and dues in view, and track trainer commission.',
            'pains' => [
                ['Members paying in instalments', 'Part payments with the balance on the bill, and a WhatsApp reminder for dues.'],
                ['Plan prices to set up', 'Import a ready-made gym service list and edit the prices.'],
                ['Trainer commission', 'Assign a trainer to a bill and track the commission payable.'],
            ],
            'features' => [
                ['Plans as service items', 'Monthly, quarterly, yearly and personal-training plans ready to bill.'],
                ['Part payments and dues', 'Balance shown on the bill and a WhatsApp reminder when it is due.'],
                ['Trainer commission', 'Track commission by trainer on the bills they bring.'],
                ['Appointments', 'Book personal-training or trial sessions.'],
                ['Bill on WhatsApp', 'Send the bill or receipt as a PDF or image.'],
                ['Reports', 'Collections, dues and daily totals.'],
            ],
            'faqs' => [
                ['Does it track membership expiry dates?', 'No. It handles billing, dues and appointments. Many gyms bill each plan as a service item and use the dues list to follow up.'],
                ['Can members pay in parts?', 'Yes. Record what is paid now; the balance stays due on the bill.'],
                ['Can I start from a ready-made list?', 'Yes. A gym service list is available to import and edit.'],
            ],
        ],

        'REPAIR_SHOP' => [
            'slug' => 'repair-shop', 'noun' => 'Repair Shop',
            'title' => 'Repair Shop Job Card & Billing Software',
            'd' => 'Take in a repair job, track it from pending to delivered, add parts and warranty, and bill with the balance due. Try the live demo.',
            'headline' => 'Job card & billing software for repair shops',
            'sub' => 'Take in a job, track it from pending to delivered, add parts and warranty, and bill with the balance due.',
            'pains' => [
                ['Jobs getting lost', 'Job cards track each repair from pending to delivered.'],
                ['Parts and labour on one bill', 'Add spare parts and labour charges to the same invoice.'],
                ['Warranty arguments', 'Set warranty days on items so the terms are on record.'],
            ],
            'features' => [
                ['Job cards', 'One card per repair with its status from pending to delivered.'],
                ['Parts and labour', 'Spare parts from your stock list plus labour charges on one bill.'],
                ['Warranty days', 'Record the warranty period on each service or part.'],
                ['Balance due', 'Advance, part payment and balance reminders on WhatsApp.'],
                ['Stock items', 'Track spare parts as stock and see when they run low.'],
                ['Bill on WhatsApp', 'Send the bill as a PDF or image.'],
            ],
            'faqs' => [
                ['Can I sell spare parts and track their stock?', 'Yes. Parts can be stock items that count down as you bill them.'],
                ['Can I take an advance when the job comes in?', 'Yes. Record a part payment and the balance stays due.'],
                ['Can I run a shop and repairs together?', 'Yes. TradeERP and ServiceERP can be used under one login.'],
            ],
        ],

        'PRINTING_PRESS' => [
            'slug' => 'printing-press', 'noun' => 'Printing Press & Digital Print Shop',
            'title' => 'Printing Press Quotation & Billing Software',
            'd' => 'Price print jobs per sheet or per 100, send quotations, convert them to invoices and keep advance and balance in order. Try the live demo.',
            'headline' => 'Quotation & billing software for printing presses',
            'sub' => 'Price jobs per sheet or per 100, send quotations, turn them into invoices, and keep advance and balance in order.',
            'pains' => [
                ['Rates by paper, size and finish', 'Keep each paper, size and finish as a service item with its own rate.'],
                ['Quotes that turn into bills', 'Create a quotation and convert it to an invoice once it is approved.'],
                ['Advance and balance', 'Part payments with balance reminders on WhatsApp.'],
            ],
            'features' => [
                ['Ready-made price list', 'A starter list for printing presses (cards, flyers, banners, stickers and more) you can import and edit.'],
                ['Quotations', 'Send a quote and convert it to an invoice when approved.'],
                ['Job cards', 'Track each job from pending to delivered.'],
                ['Quantity-based items', 'Bill per sheet, per 100 or per piece by setting the quantity.'],
                ['GST or Bill of Supply', 'Choose how each bill is taxed. Please confirm with your accountant.'],
                ['Bill on WhatsApp', 'Send the bill as a PDF or image.'],
            ],
            'faqs' => [
                ['Can I price jobs per sheet or per 100?', 'Yes. Make each rate a service item and change the quantity on the bill. Quantity tiers can be separate items.'],
                ['Can I turn a quotation into an invoice?', 'Yes. An approved quotation converts to an invoice carrying its items over.'],
                ['Is there a starter price list?', 'Yes. A printing press list is available in the app; its prices are placeholders to replace with your own.'],
            ],
        ],

        'PHOTOGRAPHY' => [
            'slug' => 'photography-studio', 'noun' => 'Photography Studio',
            'title' => 'Photography Studio Quotation & Billing Software',
            'd' => 'Quote wedding and event packages, take an advance and track the balance until delivery. Works on your phone. Try the live demo.',
            'headline' => 'Quotation & billing software for photographers',
            'sub' => 'Quote wedding and event packages, take an advance, and track the balance until the album is delivered.',
            'pains' => [
                ['Packages and add-ons', 'Keep every package and extra as a service item and quote it in minutes.'],
                ['Advance and balance', 'Part payments with the remaining balance shown on the bill.'],
                ['The shoot schedule', 'Appointments for shoots; assign staff and track their commission.'],
            ],
            'features' => [
                ['Quotations', 'Send a package quote and convert it to an invoice when confirmed.'],
                ['Appointments', 'Book shoots and move each booking through its status.'],
                ['Advance and balance', 'Record part payments; the balance stays due on the bill.'],
                ['Staff and commission', 'Assign photographers or editors and track commission.'],
                ['Ready-made service list', 'A photography starter list you can import and edit.'],
                ['Google Reviews QR', 'Help happy clients post a genuine review after delivery.'],
            ],
            'faqs' => [
                ['Can I take an advance when a client books?', 'Yes. Record the advance as a part payment; the balance stays due.'],
                ['Can I send a quotation first?', 'Yes. Create a quotation and convert it to an invoice once the client agrees.'],
                ['Can I keep my packages in the app?', 'Yes. Add each package as a service item, or import a CSV.'],
            ],
        ],

        'RESTAURANT_CAFE' => [
            'slug' => 'restaurant-cafe', 'noun' => 'Restaurant & Cafe',
            'title' => 'Restaurant & Cafe Counter Billing Software',
            'd' => 'Turn your printed menu into an item list, bill in taps, accept UPI at the counter and see your best sellers. Try the live demo.',
            'headline' => 'Billing software for restaurants & cafes',
            'sub' => 'Turn your printed menu into an item list, bill in a few taps, accept UPI at the counter, and see your best sellers.',
            'pains' => [
                ['Typing the whole menu', 'Photograph the menu and review the items the app reads before saving.'],
                ['The counter queue', 'Tap-to-bill cards on a phone, with a UPI QR showing the bill amount at checkout.'],
                ['Not knowing the best sellers', 'Reports show top items and daily sales.'],
            ],
            'features' => [
                ['Menu from a photo', 'Scan a printed menu and review the items before they are saved.'],
                ['Tap-to-bill cards', 'Fast counter billing on a phone or computer.'],
                ['UPI at checkout', 'Show a UPI QR with the exact bill amount.'],
                ['Cash memo by voice or text', 'Speak or type a line like “coffee 2” and add it to the bill.'],
                ['Bill on WhatsApp', 'Send the bill as a PDF or image.'],
                ['Reports', 'Top items, daily sales and expenses.'],
            ],
            'faqs' => [
                ['Does it do table orders or kitchen tickets?', 'No. It is a counter-billing tool. It does not handle table management or kitchen order tickets.'],
                ['Can I import my menu?', 'Yes. Import a CSV, or scan a photo of the printed menu and check it first.'],
                ['Can customers pay by UPI?', 'Yes. Save your UPI ID once and the bill shows a QR for the amount.'],
            ],
        ],

        'AUTOMOBILE_GARAGE' => [
            'slug' => 'garage', 'noun' => 'Automobile Garage',
            'title' => 'Garage Job Card & Billing Software',
            'd' => 'Open a job card, add parts and labour, set warranty and bill with the balance due, for cars, bikes and more. Try the live demo.',
            'headline' => 'Job card & billing software for garages',
            'sub' => 'Open a job card, add parts and labour, set warranty, and bill with the balance due, for cars, bikes and more.',
            'pains' => [
                ['Jobs and vehicles to keep track of', 'A job card per vehicle with its status from pending to delivered.'],
                ['Parts and labour on one bill', 'Spare parts and labour charges together on a single invoice.'],
                ['Customers paying later', 'Advance, part payment and balance reminders on WhatsApp.'],
            ],
            'features' => [
                ['Job cards', 'Track each vehicle’s work from pending to delivered.'],
                ['Parts and labour', 'Add parts from your stock list and labour charges to the bill.'],
                ['Warranty days', 'Record the warranty period on services and parts.'],
                ['Staff and commission', 'Assign mechanics to a job and track commission.'],
                ['Balance due', 'See what each customer still owes and remind them on WhatsApp.'],
                ['Bill on WhatsApp', 'Send the bill as a PDF or image.'],
            ],
            'faqs' => [
                ['Can I track spare parts stock?', 'Yes. Parts can be stock items that count down as you bill them.'],
                ['Can I take an advance on a big job?', 'Yes. Record a part payment and the balance stays due.'],
                ['Does it work for bike and two-wheeler workshops?', 'Yes. A job card and invoice work for any vehicle or workshop job.'],
            ],
        ],
    ],
];
