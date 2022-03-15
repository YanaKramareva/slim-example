<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use App\Validator;
use App\SchoolRepository;
use App\UserRepository;
use App\PostRepository;
use App\CourseRepository;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;


session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();
$repo = new App\UserRepository();
$users = $repo->all();



$app->get('/foo', function ($req, $res) {
    // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
    // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
    // Например, можно ввести тип success и отражать его зелёным цветом (на Хекслете такого много)
    $this->get('flash')->addMessage('success', 'This is a message');

    return $res->withRedirect('/bar');
});

$app->get('/bar', function ($req, $res) {
    // Извлечение flash сообщений установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages();
    print_r($messages); // => ['success' => ['This is a message']]

    $params = ['flash' => $messages];
    return $this->get('renderer')->render($res, 'users/index.phtml', $params);
});


$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Hexlet!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');

});

$app->get('/world', function ($request, $response) {
    $response->getBody()->write('Hello, world!');
    return $response;
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/courses/{courseId}/lessons/{id}', function ($request, $response, array $args) {
    $courseId = $args['courseId'];
    $id = $args['id'];
    return $response->write("Course id: {$courseId}")
        ->write("Lesson id: {$id}");
});

$app->get('/users', function ($request, $response) use ($users) {;
    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($users, fn($user)=>str_contains($user['name'], $term));
    $messages = $this->get('flash')->getMessages();
    print_r($messages); // => ['success' => ['This is a message']]
    $params = ['users' => $filteredUsers,
        'flash' => $messages];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');


$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['id'=> '', 'name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.html", $params);
})->setName('userForm');

$app->get('/courses', function ($request, $response) use ($repo) {
    $term = $request->getQueryParam('term');
    $filteredCourses = array_filter($this->courses, fn($course)=>str_contains($course, $term));
    $params = ['courses' => $filteredCourses];
    return $this->get('renderer')->render($response, 'users/courses.phtml', $params);
})->setName('courses');

$app->get('/users/{id}', function ($request, $response, $args) use ($users) {
    $filteredUser = array_filter($users, fn($user)=>str_contains($user, $args['id']));
    $params = ['users' => $filteredUser];
    if (empty($filteredUser)){
        return $response->write('not found')->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('filteredUser');

$app->post('/usersRepo', function ($request, $response) use ($repo, $router) {
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);

    $this->get('flash')->addMessage('success', 'This is a message');

    if (count($errors) === 0) {
        $repo->save($user);
        return $response->withRedirect($router->urlFor('users'), 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.html', $params);
});

$app->post('/users', function ($request, $response) {
    // Информация о добавляемом товаре
    $user = $request->getParsedBodyParam('user');

    // Данные корзины
    $users = json_decode($request->getCookieParam('users', json_encode([])));

    // Добавление нового товара
    $users[] = $user;

    // Кодирование корзины
    $encodedCart = json_encode($users);

    // Установка новой корзины в куку
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/users');
});


$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
    $repo = new App\UserRepository();
    $id = $args['id'];
    $repo->destroy($id);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});


$schoolRepo = new SchoolRepository();

$app->get('/schools', function ($request, $response)  use ($schoolRepo){
    $schools = $schoolRepo->all();
    $term = $request->getQueryParam('term');
    $filteredSchools = array_filter($schools, fn($school)=>str_contains($school['name'], $term));
    $params = ['schools' => $filteredSchools];
    return $this->get('renderer')->render($response, "schools/index.phtml", $params);
})->setName('schools');

$app->get('/schools/new', function ($request, $response) {
    $params = [
        'school' => ['id'=> '', 'name' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "schools/new.html", $params);
})->setName('schoolForm');

$app->get('/schools/{id}', function ($request, $response, array $args) use ($schoolRepo) {
    $id =  $args['id'];
    $school = $schoolRepo->find($id);
    var_dump($school);
    if (!$school) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    $params = ['schools' => $school];
    return $this->get('renderer')->render($response, "schools/index.phtml", $params);

})->setName('school');

$app->post('/schools', function ($request, $response) use ($schoolRepo, $router) {
    $validator = new App\SchoolValidator();
    $school = $request->getParsedBodyParam('school');
    $errors = $validator->validate($school);

    if (count($errors) === 0) {
        $schoolRepo->save($school);
        $this->get('flash')->addMessage('success', 'School has been created');
        // Обратите внимание на использование именованного роутинга
        $url = $router->urlFor('schools');
        return $response->withRedirect($url);
    }
    $params = [
        'school' => $school,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'schools/new.html', $params);
});

$app->get('/schools/{id}/edit', function ($request, $response, array $args) {
    $repo = new App\SchoolRepository();
    $id = $args['id'];
    $school = $repo->find($id);
    if (!$school) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    $params = [
        'school' => $school,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'schools/edit.phtml', $params);
})->setName('editSchool');

$app->patch('/schools/{id}', function ($request, $response, array $args) use ($router)  {
    $repo = new App\SchoolRepository();
    $id = $args['id'];
    $school = $repo->find($id);
    $data = $request->getParsedBodyParam('school');

    $validator = new App\SchoolValidator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        // Ручное копирование данных из формы в нашу сущность
        $school['name'] = $data['name'];
        $this->get('flash')->addMessage('success', 'School has been updated');
        $repo->edit($school);
        $url = $router->urlFor('editSchool', ['id' => $school['id']]);
        return $response->withRedirect($url);
    }

    $params = [
        'school' => $school,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'schools/edit.phtml', $params);
});

$app->post('/schools/{id}/delete', function ($request, $response, array $args) {
    $repo = new App\SchoolRepository();
    $id = $args['id'];
    $school = $repo->find($id);
    if (!$school) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    $params = [
        'school' => $school,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'schools/delete.phtml', $params);
})->setName('deleteSchool');


$app->delete('/schools/{id}/delete', function ($request, $response, array $args) use ($router) {
    $repo = new App\SchoolRepository();
    $id = $args['id'];
    $repo->destroy($id);
    $this->get('flash')->addMessage('success', 'School has been deleted');
    return $response->withRedirect($router->urlFor('schools'));
});

/*
 * Реализуйте следующие обработчики:

Форма создания нового поста: GET /posts/new
Создание поста: POST /posts
Посты содержат два поля name и body, которые обязательны к заполнению. Валидация уже написана.

Реализуйте вывод ошибок валидации в форме.
После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:

Post has been created
templates/posts/new.phtml
Форма для создания поста

Подсказки
Для редиректов в обработчиках используйте именованный роутинг

 */
$PostRepo = new App\PostRepository();
$posts = $PostRepo->all();

$app->get('/posts', function ($request, $response) use ($posts) {
    $page = $request->getQueryParam('page');
    $countPosts = count($posts);
    $chunkLength = 5;
    $numOfPages = $countPosts > $chunkLength ?  (ceil($countPosts / $chunkLength)) :  1;
    $offset = $page <= 1 ? 0 : $page * $chunkLength - $chunkLength;
    $filteredPosts = collect($posts)->slice($offset,$chunkLength)->all();
    $messages = $this->get('flash')->getMessages();
    print_r($messages); // => ['success' => ['This is a message']]
    $params = ['posts' => $filteredPosts,
        'page' => 1,
        'flash' => $messages,
        'numberOfPages' =>$numOfPages];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

/*
Реализуйте следующие обработчики:

Форма создания нового поста: GET /posts/new
Создание поста: POST /posts
Посты содержат два поля name и body, которые обязательны к заполнению. Валидация уже написана.

Реализуйте вывод ошибок валидации в форме.
После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:

Post has been created
templates/posts/new.phtml
Форма для создания поста

Подсказки
Для редиректов в обработчиках используйте именованный роутинг
*/
$app->get('/posts/new', function ($request, $response) {
    $params = [
        'post' => ['id'=> '', 'name' => '', 'body' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "posts/new.html", $params);
})->setName('postForm');

$app->post('/posts', function ($request, $response) use ($PostRepo, $router) {
    $validator = new \App\PostValidator();
    $post = $request->getParsedBodyParam('post');
    $errors = $validator->validate($post);

    if (count($errors) === 0) {
        $PostRepo->save($post);
        $this->get('flash')->addMessage('success', 'Post has been created');
        $url = $router->urlFor('posts');
        return $response->withRedirect($url);
    }
    $params = [
        'posts' => $post,
        'errors' => $errors
    ];
$response = $response->withStatus(422);
return $this->get('renderer')->render($response, 'posts/new.html', $params);
});


$app->get('/posts/{id}', function ($request, $response, array $args) use ($PostRepo) {
    $id =  $args['id'];
    $post = $PostRepo->find($id);
    if (!$post) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    $params = [
        'post' => $post
    ];
    return $this->get('renderer')->render($response, 'posts/show.phtml', $params);

})->setName('post');

/*
 * Реализуйте следующие обработчики:

Форма редактирования поста: GET /posts/{id}/edit
Обновление поста: PATCH /posts/{id}
Посты содержат поля name и body, которые обязательны к заполнению. Валидация уже написана. После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:

Post has been updated
templates/posts/edit.phtml
Форма для редактирования поста. Общая часть формы уже выделена в шаблон _form, подключите его по аналогии с templates/posts/new.phtml.

Подсказки
Для редиректов в обработчиках используйте именованный роутинг
 */

$app->get('/posts/{id}/edit', function ($request, $response, array $args) use ($PostRepo) {
    $post = $PostRepo->find($args['id']);
    $params = [
        'post' => $post,
        'errors' => [],
        'postData' => $post
    ];
    return $this->get('renderer')->render($response, 'posts/edit.phtml', $params);
});

$app->patch('/posts/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $post = $repo->find($args['id']);
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\PostValidator();
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        $post['name'] = $postData['name'];
        $post['body'] = $postData['body'];
        $repo->save($post);
        $this->get('flash')->addMessage('success', 'Post has been updated');
        return $response->withRedirect($router->urlFor('posts'));
    }

    $params = [
        'post' => $post,
        'postData' => $postData,
        'errors' => $errors
    ];

    return $this->get('renderer')
        ->render($response->withStatus(422), 'posts/edit.phtml', $params);
});

$app->post('/posts/{id}/delete', function ($request, $response, array $args) use ($PostRepo) {
    $post = $PostRepo->find($args['id']);
    $id = $args['id'];
    $PostRepo->destroy($id);
    $this->get('flash')->addMessage('success', 'Post has been removed');
    $params = [
        'post' => $post,
    ];


    return $this->get('renderer')->render($response, 'posts/delete.phtml', $params);
});

/*
 * Реализуйте удаление поста (обработчик DELETE /posts/{id})

После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:

Post has been removed
templates/posts/index.phtml
Реализуйте вывод списка постов и добавьте к каждому посту кнопку на удаление.
Подсказки
Для редиректов в обработчиках используйте именованный роутинг
 */
$app->delete('/posts/{id}', function ($request, $response, array $args) use ($PostRepo, $router) {
    $id = $args['id'];
    $PostRepo->destroy($id);
    $this->get('flash')->addMessage('success', 'Post has been removed');
    return $response->withRedirect($router->urlFor('posts'));
});

/*
 * Реализуйте два обработчика

POST /cart-items для добавления товаров в корзину
DELETE /cart-items для очистки корзины
Корзина должна храниться на клиенте в куках. Кроме самого товара, необходимо хранить количество единиц. Добавление товара приводит к увеличению счетчика и редиректу на главную. Подробнее смотрите в шаблоне. Для сериализации данных используйте json_encode().
 */

$app->get('/carts', function ($request, $response) {
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
    $params = [
        'cart' => $cart
    ];
    return $this->get('renderer')->render($response, 'carts/index.phtml', $params);
});

// BEGIN (write your solution here)
$app->post('/cart-items', function ($request, $response) {
    $item = $request->getParsedBodyParam('item');
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);

    $id = $item['id'];
    if (!isset($cart[$id])) {
        $cart[$id] = ['name' => $item['name'], 'count' => 1];
    } else {
        $cart[$id]['count'] += 1;
    }

    $encodedCart = json_encode($cart);
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/');
});


$app->delete('/cart-items', function ($request, $response) {

    $cart = [];
    // Кодирование корзины
    $encodedCart = json_encode($cart);

    // Установка новой корзины в куку
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/');
});

/*
 * В этой практике необходимо реализовать систему аутентификации. В простейшем случае она состоит из двух маршрутов:

POST /session - создает сессию
DELETE /session - удаляет сессию
После выполнения каждого из этих действий происходит редирект на главную.

templates/index.phtml
Если пользователь не аутентифицирован, то ему показывается форма с текстом "Sign In" полем для ввода имени и пароля. Если аутентифицирован, то его имя и форма с кнопкой "Sign Out".

Для полей формы используйте имена user[name] и user[password].

public/index.php
Реализуйте указанные выше маршруты и дополнительно маршрут /.

Список пользователей с именами и паролями доступен в массиве $users. Обратите внимание на то что пароль хранится в зашифрованном виде (их не хранят в открытом виде). Это значит, что при сравнении необходимо шифровать пароль, приходящий от пользователя, и сравнивать хеши.

Если имя или пароль неверные, то происходит редирект на главную, и показывается флеш сообщение Wrong password or name.

 */

$users = [
    ['name' => 'admin', 'passwordDigest' => hash('sha256', 'secret')],
    ['name' => 'mike', 'passwordDigest' => hash('sha256', 'superpass')],
    ['name' => 'kate', 'passwordDigest' => hash('sha256', 'strongpass')]
];

// BEGIN (write your solution here)

$app->get('/sessions', function ($request, $response) {
    return $this->get('renderer')->render(
        $response,
        'index.phtml',
        [
            'flash' => $this->get('flash')->getMessages(),
            'user'  => $_SESSION['user'] ?? null,
        ]
    );
});

$app->post('/session', function ($request, $response) use ($users)  {
    $userData = $request->getParsedBodyParam('user');
    $user = collect($users)->where('name', $userData['name'])->where('passwordDigest', hash('sha256', $userData['password']));
var_dump($user);
    if (!$user) {
        $this->get('flash')->addMessage('error', 'Wrong password or name');
    }

    $_SESSION['user'] = $user;

    return $response->withRedirect('/');
});

$app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect('/');
});
/*
 * Реализуйте аутентификацию.
 * Она состоит из формы входа (достаточно знать емейл) и кнопки выхода, которая появляется после аутентификации.
 */

$app->get('/login', function ($request, $response) {
    return $this->get('renderer')->render(
        $response,
        'users/login.phtml',
        [
            'flash' => $this->get('flash')->getMessages(),
            'user'  => $_SESSION['user'] ?? null,
        ]
    );
})->setName('login');

$app->post('/login', function ( $request,  $response) use ($router, $users){
    $userData = $request->getParsedBodyParam('user');
    $password = hash('sha256', $userData['password']);
    $user = collect($users)->firstWhere('name', $userData['name']);
    $userPassword = collect($users)->firstWhere('passwordDigest', $password);
    if (!$user ) {
        $this->get('flash')->addMessage('error', 'Wrong name');
        return $response->withRedirect($router->urlFor('login'));
    }
    if (!$userPassword) {
        $this->get('flash')->addMessage('error', 'Wrong password');
        return $response->withRedirect($router->urlFor('login'));
    }
    $_SESSION['user'] = $user;

    return $response->withRedirect($router->urlFor('login'));
});

$app->delete('/login', function ($request, $response) use ($router) {
    $_SESSION = [];
    session_destroy();

    return $response->withRedirect($router->urlFor('login'));
});

$app->run();
