<?php

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Render
{
    private static Render|null $self_cast = null;
    private Environment $env;
    private Response $response;
    private Request $request;
    public array $args = [];

    public Session|null $Session;
    protected Pg|null $PgCommon;

    public function __construct ()
    {
        $this->env = new Environment(new FilesystemLoader(CONFIG_TEMPLATES_PATH), [
            'debug' => true
        ]);
        $this->env->addExtension(new DebugExtension());
    }

    public static function getInstance (): self
    {
        if (self::$self_cast != null) {
            return self::$self_cast;
        }
        self::$self_cast = new self();
        return self::$self_cast;
    }

    public function set (Request $request, Response $response, array $args): void
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
    }

    public function setSession($_Session): void
    {
        $this->Session = $_Session;
    }

    public function getRequest (): Request
    {
        return $this->request;
    }

    public function getResponse (): Response
    {
        return $this->response;
    }

    public function classPgCommon (): void
    {
        if (! isset($this->PgCommon)) {
            $this->PgCommon = new Pg('COMMON');
        }
    }

    public function getParams (): array
    {
        global $NsGlobal;
        $params = [];
        switch ($this->request->getMethod()) {
            case 'GET':
                $params = $this->request->getQueryParams();
                break;
            case 'POST':
                $params = $this->request->getParsedBody();
                break;
            default:
                break;
        }
        if (count($this->args) > 0) {
            $params = array_merge($params, $this->args);
        }
        if (is_array($params)) {
            $NsGlobal->setParamsData($params);
        }
        return $params ?? [];
    }

    public function existParams (array $require = []): void
    {
        global $NsGlobal;
        if (count($require) > 0) {
            $params = $NsGlobal->getParamsData();
            foreach ($require AS $r) {
                if (! key_exists($r, $params)) {
                    throw new ErrorHandler('error', "Not Found Parameter. ($r)");
                }
            }
        }
    }

    public function wrap (callable $callback): callable
    {
        return function (Request $request, Response $response, array $args = []) use ($callback): callable|object {
            global $NsGlobal, $i18n;
            $NsGlobal->setParamsData(); // 초기화
            $NsGlobal->setErrorMessage(); // 초기화
            $this->set($request, $response, $args);
            try {
                if (! is_callable($callback)) {
                    throw new ErrorHandler('error', 'NOT FOUND Callback.');
                }
                $params = $this->getParams();
                if (str_starts_with($request->getUri()->getPath(), '/admin/')) {
                    $files = $request->getUploadedFiles();
                    if (count($files) > 0) {
                        $params['upload'] = $files;
                    }
                }
                if (CONF_ONLY_PLATFORM_MODE === true && in_array($request->getUri()->getPath(), CONF_CHECK_PATH_URL)) {
                    try {
                        $this->classPgCommon();
                        $this->PgCommon->query('SELECT salt_key FROM account WHERE uid = $1', [$params['uuid']]);
                        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
                        $decoded = JWT::decode($token, new Key($this->PgCommon->fetchOne(), 'HS256'));
                        $params = array_merge((ARRAY)$decoded, $params);
                    } catch (Throwable $e) {
                        throw new ErrorHandler('error', $i18n->t('msg_token_verification_failed'));
                    }
                }
                return $callback ($params);
            } catch (Throwable $e) {
                return $this->nsErrorReturn($e->getMessage(), $e);
            }
        };
    }

    public function template (string $templates, array $data = []) : Response
    {
        try {
            $render = $this->env->load($templates)->render($data);
            $this->response->getBody()->write($render);
            return $this->response;
        } catch (Throwable $e) {
            global $NsGlobal;
            $error_message = $NsGlobal->getErrorMessage() ?? $e->getMessage();
            return $this->nsErrorReturn($error_message, $e);
        }
    }

    public function view (string $data = '') : Response
    {
        try {
            $this->response->getBody()->write($data);
            return $this->response;
        } catch (Throwable $e) {
            global $NsGlobal;
            $error_message = $NsGlobal->getErrorMessage() ?? $e->getMessage();
            return $this->nsErrorReturn($error_message, $e);
        }
    }

    public function nsErrorReturn($_message, ErrorHandler|Throwable $_error): Response
    {
        global $_START_TIME, $_NS_SQ_REFRESH_FLAG;
        $_END_TIME = Useful::microtimeFloat();
        $duration = Decimal::set($_END_TIME)->minus($_START_TIME)->getValue();

        if ($_NS_SQ_REFRESH_FLAG) {
            $_NS_SQ_REFRESH_FLAG = false;
        }

        $this->response = $this->response->withHeader('Content-type', 'application/json');
        $ns_xhr_return = [
            'code' => $_error->error_type ?? 'error', // 타입이 따로 없다면 error
            'message' => $_message,
            'add_data' => [],
            'duration' => (DOUBLE)$duration,
            'conf_web_version' => CONF_WEB_VERSION
        ];
        if (CONF_DEBUG_ERROR_DISPLAY) {
            $ns_xhr_return['trace'] = $_error->getTrace();
        }
        $content = json_encode(['ns_xhr_return' => $ns_xhr_return], JSON_UNESCAPED_UNICODE);
        $this->response->getBody()->write($content);
        return $this->response;
    }

    public function nsXhrReturn($_code, $_message = null, $_add_data = null): Response
    {
        global $_START_TIME;
        $_END_TIME = Useful::microtimeFloat();
        $duration = Decimal::set($_END_TIME)->minus($_START_TIME)->getValue();

        $push_data = null;
        if (isset($this->Session) && $this->Session->checkLpData()) {
            $this->Session->sqAppend('PUSH', ['LP_REQUEST' => true]); // 받지 않은 LP 데이터가 있다면 LP를 강제로 요청하도록
        }
        if ($_code == 'success' && isset($this->Session) && $this->Session->existsPushData()) {
            $push_data = $this->Session->getPushData();
        }

        $this->response = $this->response->withHeader('Content-type', 'application/json');
        $ns_xhr_return = [
            'code' => $_code,
            'message' => $_message,
            'add_data' => $_add_data,
            'push_data' => $push_data,
            'duration' => (DOUBLE)$duration,
            'conf_web_version' => CONF_WEB_VERSION
        ];
        $content = json_encode(['ns_xhr_return' => $ns_xhr_return], JSON_UNESCAPED_UNICODE);
        // $content = Useful::gzipOut($content);
        $this->response->getBody()->write($content);
        return $this->response;
    }

    public function redirect ($path = '', $code = 0): Response
    {
        $this->response = $this->response->withHeader('Location', $path);
        if ($code !== 0) {
            $this->response->withStatus($code);
        }
        return $this->response;
    }
}