#!/bin/bash

read -p "Enter your new plugin slug (e.g. my-awesome-plugin): " slug
read -p "Enter your new plugin name (e.g. My Awesome Plugin): " name

# Rename main plugin file and POT
if [ -f "plugin-name.php" ]; then
  mv plugin-name.php "${slug}.php"
fi

if [ -f "languages/plugin-name.pot" ]; then
  mv "languages/plugin-name.pot" "languages/${slug}.pot"
fi

# Replace content inside all files
grep -rl "plugin-name" . | xargs sed -i "s/plugin-name/${slug}/g"
grep -rl "JRVT Plugin Boilerplate" . | xargs sed -i "s/JRVT Plugin Boilerplate/${name}/g"

# Optional: Rename root folder
base_dir=$(basename "$PWD")
parent_dir=$(dirname "$PWD")

read -p "Rename folder '${base_dir}' to '${slug}'? (y/n): " confirm
if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
  cd ..
  mv "$base_dir" "$slug"
  echo "Folder renamed to ${slug}."
fi

echo "✅ Setup complete: '${slug}' with name '${name}'"
