CREATE DATABASE IF NOT EXISTS simulasi_keamanan;
USE simulasi_keamanan;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role, nama) VALUES
('admin', 'admin123', 'admin', 'Administrator'),
('user1', 'user123', 'user', 'Pengguna Biasa'),
('operator', 'op123', 'user', 'Operator Sistem');

CREATE TABLE IF NOT EXISTS pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT,
    no_telp VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO pegawai (nip, nama, alamat, no_telp) VALUES
('198001012005011001', 'Dr. Andi Pratama, M.Kom', 'Jl. Merdeka No. 123, Jakarta Pusat', '081234567890'),
('198102152006021002', 'Ir. Budi Santoso, MT', 'Jl. Sudirman No. 45, Bandung', '082345678901'),
('198203202007031003', 'Siti Rahmawati, S.Kom, MMSI', 'Jl. Diponegoro No. 67, Surabaya', '083456789012'),
('198304252008041004', 'Doni Wijaya, S.T, M.T', 'Jl. Gatot Subroto No. 89, Yogyakarta', '084567890123'),
('198405302009051005', 'Rina Marlina, S.E, M.Ak', 'Jl. Ahmad Yani No. 12, Semarang', '085678901234'),
('198506042010061006', 'Ahmad Fauzi, S.Si, M.Sc', 'Jl. Pahlawan No. 34, Medan', '086789012345'),
('198607082011071007', 'Nurul Hidayah, S.Pd, M.Pd', 'Jl. Pendidikan No. 56, Makassar', '087890123456'),
('198708122012081008', 'Eko Purwanto, S.H, M.H', 'Jl. Hukum No. 78, Palembang', '088901234567'),
('198809162013091009', 'Fitri Handayani, S.Farm, Apt', 'Jl. Sehat No. 90, Denpasar', '089012345678'),
('198910202014101010', 'Hendra Gunawan, S.IP, M.Si', 'Jl. Ilmu No. 11, Malang', '081122334455');
