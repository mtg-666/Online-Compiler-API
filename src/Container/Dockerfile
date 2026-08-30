FROM alpine:3.14
RUN apk add --no-cache bash
RUN apk add gcc
RUN apk add g++
RUN apk add python3

# for java we have to set path 
RUN apk add openjdk8
ENV JAVA_HOME=/usr/lib/jvm/java-1.8-openjdk
ENV PATH="$JAVA_HOME/bin:${PATH}"