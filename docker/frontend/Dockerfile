FROM node:22-alpine

WORKDIR /app

COPY docker/frontend/docker-entrypoint.sh /usr/local/bin/frontend-entrypoint
RUN chmod +x /usr/local/bin/frontend-entrypoint

COPY frontend/package.json frontend/package-lock.json ./
RUN frontend-entrypoint true

COPY frontend/ .

EXPOSE 5173

ENTRYPOINT ["frontend-entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
