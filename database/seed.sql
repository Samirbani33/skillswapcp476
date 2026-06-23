USE skillswap;

-- Users (all passwords = "password123")
INSERT INTO users (first_name, last_name, email, password_hash, role, bio) VALUES
('Alice',  'Chen',    'alice@mylaurier.ca',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', '4th year CS student. Expert in Python, web dev, and data structures.'),
('Bob',    'Singh',   'bob@mylaurier.ca',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Business student looking for affordable tech help.'),
('Priya',  'Patel',   'priya@mylaurier.ca',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'Graphic designer with 3 years of freelance experience.'),
('Carlos', 'Ruiz',    'carlos@mylaurier.ca', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Engineering student needing design help.'),
('Admin',  'User',    'admin@skillswap.ca',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',    'Platform administrator.');

-- Services (category_id: 1=Programming,2=Tutoring,3=Design,4=Photography,5=Writing,6=Other)
INSERT INTO services (user_id, category_id, title, description, price) VALUES
(1, 2, 'Python & Data Structures Tutoring',  'Help with CP164, CP312, or any Python assignment. Flexible scheduling around classes.', 25.00),
(1, 1, 'Full-Stack Web Dev Help',            'HTML/CSS/JS/PHP debugging and pair-programming. Can help with CP476B assignments.', 40.00),
(3, 3, 'Logo & Brand Identity Design',       'Professional logo for your project or student club. 3 revision rounds included.', 50.00),
(3, 5, 'Resume & LinkedIn Makeover',         'Resume design tailored for tech internships and co-op applications. ATS-friendly format.', 30.00),
(1, 4, 'Campus Photography Session',         'Headshots or event photos anywhere on campus. 20 edited photos delivered within 48 hours.', 60.00),
(3, 3, 'Presentation Slide Design',          'Turn your rough notes into a polished, professional slide deck. Any topic.', 35.00);

-- Bookings
INSERT INTO bookings (service_id, customer_id, provider_id, booking_status, message) VALUES
(1, 2, 1, 'Completed', 'Hi Alice, I need help with my CP164 lab due Friday. Available Wednesday evening?'),
(3, 4, 3, 'Active',    'Hello Priya, I need a logo for my student club. Can we discuss the concept?'),
(2, 2, 1, 'Pending',   'I have a bug in my PHP assignment I cannot figure out. Can you help me?'),
(4, 4, 3, 'Rejected',  'Need my resume done by tomorrow morning — is that possible?'),
(5, 2, 1, 'Completed', 'Need headshots for my LinkedIn profile. Available Saturday afternoon?');

-- Reviews (completed bookings only)
INSERT INTO reviews (booking_id, reviewer_id, rating, comment) VALUES
(1, 2, 5, 'Alice was incredibly patient and explained everything clearly. Got a 90 on my lab — highly recommend!'),
(5, 2, 4, 'Great photos, very professional. Delivered on time. Would book again.');

-- Conversations & Messages
INSERT INTO conversations (user_a_id, user_b_id) VALUES (1, 2);
INSERT INTO messages (conversation_id, sender_id, content) VALUES
(1, 2, 'Hey Alice, are you available this Wednesday around 6pm?'),
(1, 1, 'Hi Bob! Yes Wednesday at 6 works perfectly. See you then.'),
(1, 2, 'Perfect, thank you so much!');
