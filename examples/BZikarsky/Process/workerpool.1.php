<?php

namespace BZikarsky\Process;

// always enable ticks at parent file
declare(ticks=1);
require_once dirname(__FILE__) . '/../../autoload.php';

$names =  array('Abbie', 'Abby', 'Abigail', 'Ada', 'Adah', 'Adaline', 'Adam', 'Addie', 'Adela', 'Adelaida', 'Adelaide', 'Adele', 'Adelia', 'Adelina', 'Adeline', 'Adell', 'Adella', 'Adelle', 'Adena', 'Adina', 'Adria', 'Adrian', 'Adriana', 'Adriane', 'Adrianna', 'Adrianne', 'Adrien', 'Adriene', 'Adrienne', 'Afton', 'Agatha', 'Agnes', 'Agnus', 'Agripina', 'Agueda', 'Agustina', 'Ai', 'Aida', 'Aide', 'Aiko', 'Aileen', 'Ailene', 'Aimee', 'Aisha', 'Aja', 'Akiko', 'Akilah', 'Alaina', 'Alaine', 'Alana', 'Alane', 'Alanna', 'Alayna', 'Alba', 'Albert', 'Alberta', 'Albertha', 'Albertina', 'Albertine', 'Albina', 'Alda', 'Alease', 'Alecia', 'Aleen', 'Aleida', 'Aleisha', 'Alejandra', 'Alejandrina', 'Alena', 'Alene', 'Alesha', 'Aleshia', 'Alesia', 'Alessandra', 'Aleta', 'Aletha', 'Alethea', 'Alethia', 'Alex', 'Alexa', 'Alexander', 'Alexandra', 'Alexandria', 'Alexia', 'Alexis', 'Alfreda', 'Alfredia', 'Ali', 'Alia', 'Alica', 'Alice', 'Alicia', 'Alida', 'Alina', 'Aline', 'Alisa', 'Alise', 'Alisha', 'Alishia', 'Alisia', 'Alison', 'Alissa', 'Alita', 'Alix', 'Aliza', 'Alla', 'Alleen', 'Allegra', 'Allen', 'Allena', 'Allene', 'Allie', 'Alline', 'Allison', 'Allyn', 'Allyson', 'Alma', 'Almeda', 'Almeta', 'Alona', 'Alpha', 'Alta', 'Altagracia', 'Altha', 'Althea', 'Alva', 'Alvera', 'Alverta', 'Alvina', 'Alyce', 'Alycia', 'Alysa', 'Alyse', 'Alysha', 'Alysia', 'Alyson', 'Alyssa', 'Amada', 'Amal', 'Amalia', 'Amanda', 'Amber', 'Amberly', 'Amee', 'Amelia', 'America', 'Ami', 'Amie', 'Amiee', 'Amina', 'Amira', 'Ammie', 'Amparo', 'Amy', 'An', 'Ana', 'Anabel', 'Analisa', 'Anamaria', 'Anastacia', 'Anastasia', 'Andera', 'Andra', 'Andre', 'Andrea', 'Andree', 'Andrew', 'Andria', 'Anette', 'Angel', 'Angela', 'Angele', 'Angelena', 'Angeles', 'Angelia', 'Angelic', 'Angelica', 'Angelika', 'Angelina', 'Angeline', 'Angelique', 'Angelita', 'Angella', 'Angelo', 'Angelyn', 'Angie', 'Angila', 'Angla', 'Angle', 'Anglea', 'Anh', 'Anika', 'Anisa', 'Anisha', 'Anissa', 'Anita', 'Anitra', 'Anja', 'Anjanette', 'Anjelica', 'Ann', 'Anna', 'Annabel', 'Annabell', 'Annabelle', 'Annalee', 'Annalisa', 'Annamae', 'Annamaria', 'Annamarie', 'Anne', 'Anneliese', 'Annelle', 'Annemarie', 'Annett', 'Annetta', 'Annette', 'Annice', 'Annie', 'Annika', 'Annis', 'Annita', 'Annmarie', 'Anthony', 'Antionette', 'Antoinette', 'Antonetta', 'Antonette', 'Antonia', 'Antonietta', 'Antonina', 'Antonio', 'Anya', 'Apolonia', 'April', 'Apryl', 'Ara', 'Araceli', 'Aracelis', 'Aracely', 'Arcelia', 'Ardath', 'Ardelia', 'Ardell', 'Ardella', 'Ardelle', 'Ardis', 'Ardith', 'Aretha');

$pool = new WorkerPool(
    function($name) 
    {
        echo "Hello $name!\n";
        usleep(rand(0, 1000000));	
    	echo "Bye $name!\n";
    },
    $names,
    50
);

$pool->start();
