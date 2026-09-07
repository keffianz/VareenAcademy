-- Run ONCE in Hostinger phpMyAdmin on database u374397808_vereen_academy, then DELETE this file.
-- Creates the demo student and staff(teacher) logins with strong unique passwords.
DELETE FROM `users` WHERE `email` = 'student@vereenacademy.com';
INSERT INTO `users` (`username`, `email`, `password`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `email_verified`)
VALUES ('vareen_student', 'student@vereenacademy.com', '$2y$10$XrZA8AmbFHJ7Wb4ZR04UT.ZEgOlJL.a1GBNiMZMQZSYAWy82f/UU2', '$2y$10$XrZA8AmbFHJ7Wb4ZR04UT.ZEgOlJL.a1GBNiMZMQZSYAWy82f/UU2', 'Vareen', 'Student', 'student', 1, 1);

DELETE FROM `users` WHERE `email` = 'staff@vereenacademy.com';
INSERT INTO `users` (`username`, `email`, `password`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `email_verified`)
VALUES ('vareen_staff', 'staff@vereenacademy.com', '$2y$10$dJVMQhCWJBOtoWvmRYhQW.Zk7FAVumOW2eyf0NCxt6WkV8xbtfz9C', '$2y$10$dJVMQhCWJBOtoWvmRYhQW.Zk7FAVumOW2eyf0NCxt6WkV8xbtfz9C', 'Vareen', 'Staff', 'teacher', 1, 1);

