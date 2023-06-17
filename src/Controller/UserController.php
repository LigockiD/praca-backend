<?php

// src/Controller/UserController.php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, PasswordHasherFactoryInterface $hasherFactory, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->hasherFactory = $hasherFactory;
        $this->userRepository = $userRepository;

    }

    #[Route('/', name: 'user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $users = $userRepository->findAll();
        $data = $serializer->serialize($users, 'json', ['groups' => 'get']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user, SerializerInterface $serializer): JsonResponse
    {
        $data = $serializer->serialize($user, 'json', ['groups' => 'get']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $bcrypt = $this->hasherFactory->getPasswordHasher('bcrypt');
        $user->setPassword($bcrypt->hash($data['password']));

        if($request->request->has('roles'))
            $user->setRoles($data['roles']);

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $data = $serializer->serialize($errors, 'json');

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $data = $serializer->serialize($user, 'json', ['groups' => 'user']);

        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, User $user, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if($request->request->has('name'))
            $user->setName($data['name']);

        if($request->request->has('email'))
            $user->setEmail($data['email']);

        if($request->request->has('password')){
            $bcrypt = $this->hasherFactory->getPasswordHasher('bcrypt');
            $user->setPassword($bcrypt->hash($data['password']));
        }

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $data = $serializer->serialize($errors, 'json');

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->flush();
        $data = $serializer->serialize($user, 'json', ['groups' => 'user']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {

        if($user = $this->userRepository->find($id)) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(["error" => "user(id=".$id.") not found."], Response::HTTP_NOT_FOUND);
    }
}
