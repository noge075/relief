<?php
namespace App\Enums;

enum EmploymentType: int {
    case STANDARD = 1; // Munkaviszony
    case HOURLY = 2;   // Órabéres
    case FIXED = 3;    // Fix megbízás
    case STUDENT = 4;  // Diák
}