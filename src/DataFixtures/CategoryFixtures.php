<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $names = ['Classiques', 'Végétariennes', 'Gourmandes', 'Piquantes'];

        foreach ($names as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);

            $this->addReference('category_' . $name, $category);
        }

        $manager->flush();
    }
}
