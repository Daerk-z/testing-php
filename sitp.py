import sys
import json
import urllib.parse
import urllib.request


ENDPOINT = (
    "https://services2.arcgis.com/NEwhEo9GGSHXcRXV/arcgis/rest/"
    "services/Paraderos_SITP_Bogot%C3%A1_D_C/FeatureServer/0/query"
)


def consultar_sitp(localidad):

    where = (
        "UPPER(NTRDIRECCION) LIKE '%"
        + localidad.upper().replace("'", "''")
        + "%'"
    )

    parametros = {
        "where": where,
        "outFields": "NTRNOMBRE,NTRDIRECCION,NTRCODIGO",
        "returnGeometry": "true",
        "f": "geojson",
        "resultRecordCount": "50"
    }

    url = ENDPOINT + "?" + urllib.parse.urlencode(parametros)

    solicitud = urllib.request.Request(
        url,
        headers={
            "User-Agent": "SENA-ADSI-Cliente-Python"
        }
    )

    with urllib.request.urlopen(solicitud, timeout=8) as respuesta:
        datos = json.loads(respuesta.read().decode("utf-8"))

    if "features" not in datos:
        return {
            "error": "La respuesta del servicio no tiene el formato esperado."
        }

    paraderos = []

    for feature in datos["features"]:

        propiedades = feature.get("properties", {})
        geometria = feature.get("geometry")

        coordenadas = (
            geometria.get("coordinates", [])
            if geometria
            else []
        )

        paraderos.append({
            "nombre": propiedades.get(
                "NTRNOMBRE",
                "Sin nombre registrado"
            ),

            "direccion": propiedades.get(
                "NTRDIRECCION",
                "Sin dirección registrada"
            ),

            "codigo": propiedades.get(
                "NTRCODIGO",
                "-"
            ),

            "longitud": (
                coordenadas[0]
                if len(coordenadas) > 0
                else None
            ),

            "latitud": (
                coordenadas[1]
                if len(coordenadas) > 1
                else None
            )
        })

    return {
        "localidad": localidad,
        "paraderos": paraderos
    }


def main():

    if len(sys.argv) < 2:

        print(json.dumps({
            "error": "No se recibió ninguna localidad."
        }, ensure_ascii=False))

        return

    localidad = sys.argv[1]

    try:

        resultado = consultar_sitp(localidad)

        print(
            json.dumps(
                resultado,
                ensure_ascii=False
            )
        )

    except Exception as e:

        print(
            json.dumps({
                "error": (
                    "No fue posible consultar el servicio SITP: "
                    + str(e)
                )
            }, ensure_ascii=False)
        )


if __name__ == "__main__":
    main()