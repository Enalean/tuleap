FROM nginx:1.23.1-alpine

RUN apk add --no-cache openssl

COPY entrypoint.sh /

EXPOSE 22 80 443

ENTRYPOINT [ "/entrypoint.sh" ]

CMD ["nginx", "-g", "daemon off;"]
