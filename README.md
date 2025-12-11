1. Подключитесь к хостингу и создайте резервную копию
cd ~/public_html
cp -r . /home/cv174341/public_html_backup  # Полная копия на всякий случай

2. Инициализируйте Git, но НЕ коммитите сразу
git init --initial-branch=main

3. Добавьте удаленный репозиторий
git remote add origin git@github.com:DimaOpalev/Bitrix24-Developer.git

4. Получите изменения из GitHub, но НЕ сливайте их
git fetch origin

5. Сохраните локальные файлы хостинга в отдельную ветку
git checkout -b host-backup
git add .
git commit -m "Резервная копия файлов хостинга на [дата]"

6. Переключитесь на основную ветку и "подтяните" репозиторий
git checkout main

# Замените файлы хостинга файлами из репозитория, 
# сохранив резервную копию в отдельной ветке
git reset --hard origin/main
