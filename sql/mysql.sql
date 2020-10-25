# phpMyAdmin SQL Dump
# version 2.5.6
# http://www.phpmyadmin.net
#
# Host: localhost
# Generation Time: Nov 09, 2004 at 05:28 PM
# Server version: 4.1.1
# PHP Version: 4.3.5
# 
# Database : `test`
# 

# --------------------------------------------------------

CREATE TABLE play_history (
    id         INT(11)      NOT NULL AUTO_INCREMENT,
    artist     VARCHAR(255) NOT NULL DEFAULT '',
    title      VARCHAR(255) NOT NULL DEFAULT '',
    album      VARCHAR(255) NOT NULL DEFAULT '',
    genre      VARCHAR(255) NULL,
    year       INT(11)      NULL,
    timeplayed TIMESTAMP    NOT NULL,
    PRIMARY KEY (`id`)
);
