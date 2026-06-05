# Build assets using Node
FROM node:18 AS node_builder
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --silent
COPY . .
RUN npm run build

# Nginx production image
FROM nginx:stable-alpine
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=node_builder /app/public /var/www/html/public

# # Ensure the storage symlink is correctly set up for serving user uploads
# RUN rm -rf /var/www/html/public/storage \
#     && ln -sf ../storage/app/public /var/www/html/public/storage
