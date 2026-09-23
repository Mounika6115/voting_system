CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('voter', 'admin') NOT NULL DEFAULT 'voter',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    party VARCHAR(100) NOT NULL,
    manifesto VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (election_id, user_id),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A default admin user is created automatically on first boot:
--   username: admin  password: Admin@123
-- (see app/seed_admin.php — change these via ADMIN_USER / ADMIN_PASS env vars)

-- Seed election
INSERT INTO elections (title, description, status) VALUES
('Student Council Election 2026', 'Vote for your preferred candidate.', 'open');

INSERT INTO candidates (election_id, name, party, manifesto) VALUES
(1, 'Aarav Sharma', 'Blue Party', 'Focus on student welfare and digital campus.'),
(1, 'Priya Patel', 'Green Party', 'Sustainability, transparency, and equal access.'),
(1, 'Rahul Verma', 'Red Party', 'Stronger events, clubs, and sports funding.');
