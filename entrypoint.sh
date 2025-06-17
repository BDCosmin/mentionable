if [ ! -f vendor/autoload_runtime.php ]; then
  composer dump-autoload --optimize
fi

exec apache2-foreground