```bash
make init
```


once
```bash
make fresh
```


mac

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --no-interaction --prefer-dist --no-scripts --ignore-platform-reqs

```
