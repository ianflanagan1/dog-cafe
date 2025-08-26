FROM docker.io/library/nginx:1.27.5-alpine AS dev
# FROM cgr.dev/chainguard/nginx:latest-dev AS dev

USER root

RUN mkdir -p \
        /var/lib/nginx/cache/fastcgi \
        /var/lib/nginx/cache/proxy \
        /var/lib/nginx/tmp/client \
        /var/lib/nginx/tmp/fastcgi \
        /var/lib/nginx/tmp/proxy \
        /var/lib/nginx/tmp/scgi \
        /var/lib/nginx/tmp/uwsgi \
    && chown -R nginx:nginx /var/lib/nginx

COPY ./static/ /static/

WORKDIR /static

RUN rm -rf /etc/nginx/conf.d

##############
# switch nginx user to UID and GUID 65532
# to align with chainguard image which we'll switch to in the future
RUN deluser nginx && \
    addgroup -g 65532 nginx && \
    adduser -D -u 65532 -G nginx -s /bin/sh nginx

RUN chown -R nginx:nginx /var/cache/nginx /var/run /var/log/nginx /var/lib/nginx /static
##############

USER nginx
ENTRYPOINT ["/usr/sbin/nginx"]
CMD ["-c", "/etc/nginx/nginx.conf", "-g", "daemon off;"]

######################################################################

FROM docker.io/library/nginx:1.27.5-alpine AS prod
# FROM cgr.dev/chainguard/nginx:latest AS prod

COPY --from=dev /usr/share/nginx /usr/share/nginx
COPY --from=dev /var/lib/nginx /var/lib/nginx
COPY --from=dev /static /static

WORKDIR /static

RUN rm -rf /etc/nginx/conf.d

# COPY ./production/venue-images/small/ /static/images/small/
# COPY ./production/venue-images/mid/ /static/images/mid/

##############
# switch nginx user to UID and GUID 65532
# to align with chainguard image which we'll switch to in the future
RUN deluser nginx && \
    addgroup -g 65532 nginx && \
    adduser -D -u 65532 -G nginx -s /bin/sh nginx

RUN chown -R nginx:nginx /var/cache/nginx /var/run /var/log/nginx /var/lib/nginx /static
##############

USER nginx
ENTRYPOINT ["/usr/sbin/nginx"]
CMD ["-c", "/etc/nginx/nginx.conf", "-g", "daemon off;"]
