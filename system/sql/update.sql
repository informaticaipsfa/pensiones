-- DROP TABLE IF EXISTS space.hist_doc;
DROP TABLE space.hist_doc;
CREATE TABLE space.hist_doc
(
  oid serial NOT NULL,
  cedu character varying(12) NOT NULL,
  cert character varying NOT NULL,
  mont numeric,
  usur character varying NOT NULL,
  fech timestamp without time zone,
  de character varying(256) NOT NULL,
  para character varying(256) NOT NULL,
  tipo integer,
  CONSTRAINT hist_doc_pkey PRIMARY KEY (oid)
);


-- DROP TABLE IF EXISTS space.mov_familia;
DROP TABLE space.mov_familia;
CREATE TABLE space.mov_familia
(
  oid serial NOT NULL,
  cedu character varying(12) NOT NULL, --Cedula
  cedf character varying(12) NOT NULL, --Cedula del familiar beneficiado
  nomb character varying NOT NULL, --Cedula del familiar beneficiado
  pocb numeric, -- Porcentaje
  cban numeric, -- Capital en Banco
  mdaa numeric, -- Monto por diferencia de Asignacion de Antiguedad
  cmue numeric, -- Monto causa o muerte
  pasfs numeric, -- Porcentaje Acto de Servicio / Fuera de Servicio
  masfs numeric, -- Monto Acto de Servicio / Fuera de Servicio
  usur character varying NOT NULL,
  esta integer, -- Estus del proceso 1:Activo 0:Reversado
  posi integer, -- Posicion en la seleccion
  fech timestamp without time zone,    
  CONSTRAINT mov_familia_pkey PRIMARY KEY (oid)
);

-- DROP TABLE IF EXISTS space.motivo_anticipo;
DROP TABLE space.motivo_anticipo;
CREATE TABLE space.motivo_anticipo
(
  oid serial NOT NULL,
  nomb character varying NOT NULL,
  obse character varying NOT NULL,
  esta integer,
  fech timestamp without time zone,
  CONSTRAINT motivo_anticipo_pkey PRIMARY KEY (oid)
);

-- DROP TABLE IF EXISTS space.usuario;
DROP TABLE space.usuario;
CREATE TABLE space.usuario AS SELECT * FROM usuario_sistema;
UPDATE space.usuario SET password='';




--- TEST ----

      SELECT 
        space.usuario.id, space.usuario.login, space.usuario.nombre, space.usuario.apellido, space.usuario.correo,
        space.usuario.status_id,  usuario_rol.rol_id, rol.status_id AS rolestatus, rol_descripcion  
      FROM space.usuario 
        JOIN usuario_rol ON space.usuario.id=usuario_rol.usuario_id 
        JOIN rol ON usuario_rol.rol_id=rol.id 

      WHERE login='admin'