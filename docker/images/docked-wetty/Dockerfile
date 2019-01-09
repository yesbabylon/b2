FROM node:0.10.38
MAINTAINER Cedric Francoys <cedricfrancoys@gmail.com>

RUN apt-get update && apt-get install -y \
  nodejs-legacy \
  openssh-client \
  sshpass \
  vim \
  procps

ADD ./app /app
WORKDIR /app

RUN npm install
EXPOSE 3000

USER root
CMD ["node", "app.js", "-p", "3000"]
