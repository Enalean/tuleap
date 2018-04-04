FROM nginx:mainline-alpine

RUN apk add --no-cache openssl

COPY entrypoint.sh /

ENTRYPOINT [ "/entrypoint.sh" ]

CMD ["nginx", "-g", "daemon off;"]
