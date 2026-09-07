-- Run ONCE in Hostinger phpMyAdmin on database u374397808_vereen_academy, then DELETE this file.
DELETE FROM `users` WHERE `email` = 'admin@vereenacademy.com';
INSERT INTO `users` (`username`, `email`, `password`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `email_verified`)
VALUES ('vareen_admin', 'admin@vereenacademy.com', '$2y$10$.Q6bDEpekmBQyl4B.q7/YOEdJ7f1CFvZSt8usg30aPRvFBfBdI/bu', '$2y$10$.Q6bDEpekmBQyl4B.q7/YOEdJ7f1CFvZSt8usg30aPRvFBfBdI/bu', 'Vareen', 'Administrator', 'admin', 1, 1);
