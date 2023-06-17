<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AdminUserFixture extends Fixture
{
    private $hasherFactory;

    public function __construct(PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->hasherFactory = $hasherFactory;
    }

    public function load(ObjectManager $manager)
    {
        $bcrypt = $this->hasherFactory->getPasswordHasher('bcrypt');
        $user = new User();
        $user->setName('admin');
        $user->setEmail('admin@wp.pl');
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setPassword($bcrypt->hash('admin'));
        $manager->persist($user);

        $manager->flush();
    }
}
