
CREATE DATABASE absensi_db;
USE absensi_db;

CREATE TABLE jabatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    jabatan_id INT,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nip VARCHAR(20) UNIQUE,
    deskripsi TEXT,
    FOREIGN KEY (jabatan_id) REFERENCES jabatan(id)
);

CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_keluar TIME,
    status ENUM('hadir', 'tidak_hadir', 'terlambat') DEFAULT 'hadir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO jabatan (nama_jabatan) VALUES 
('Guru Matematika'), ('Guru Bahasa Indonesia'), ('Guru IPA'), 
('Kepala Sekolah'), ('Wakil Kepala Sekolah'), ('Staff TU');

INSERT INTO users (nama, email, password, jabatan_id, qr_code, role) VALUES 
('Admin', 'admin@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'ADMIN001', 'admin');
