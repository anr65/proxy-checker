# Proxy checker by Anar Muradov

 
Сервис позволяет проверить на работоспособность публичный прокси

В работе сервиса использована многпоточность путем запуска воркеров в
супервизоре

Ниже представлена конфигурация:

```
[program:proxy-checker]
process_name=%(program_name)s_%(process_num)02d
command=php /full/path/to/project/proxy-checker/artisan queue:work --timeout=0 --tries=1 --delay=2
autostart=true
autorestart=true
user=root
numprocs=8
redirect_stderr=true
stdout_logfile=/root/laravel_logs/proxy-checker.log
stopwaitsecs=5
```

Количество конкурентных процессов выставлено на 8, для обеспечения оптимальной нагрзки на серве


Проект хостится на https://proxy-check.ravs.pro и доступен для тестирования

В ходе разработки применяется следующая методология

Геолокация ip проверяется путем запроса на внешний сервис ip-api.com
Работоспособность прокси проверяется путем cURL с тремя типами http, https и socks5 

При успешном подключении - в БД заносится тип подключения и соответствующий статус

Дополнительно реализовано:
- История запросов (история задач, а также подробная инфаормация в модальном окне при клике на задачу)
- Анимация прогресса выполнения задачи
