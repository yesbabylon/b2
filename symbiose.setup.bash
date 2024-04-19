if [ "$WITH_SB" = true ]; then
  print_color "yellow" "Clone an setup of Symbiose started..."
  docker exec -ti "$USERNAME" bash -c "
  mv packages packages-core
  yes | git clone -b dev-2.0 https://github.com/yesbabylon/symbiose.git packages
  mv packages-core/{core,demo} packages/
  rm -rf packages-core
  "
fi
